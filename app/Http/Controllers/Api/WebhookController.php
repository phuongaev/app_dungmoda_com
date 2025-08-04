<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cash;
use App\Models\Label;
use App\Models\TelegramHistory;
use App\Services\TelegramService;
use App\Models\PosOrder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Encore\Admin\Config\Config;

class WebhookController extends Controller
{
    private $allowedConfigKeys = [
        'pancake_api_key',
        'pancake_token',
    ];

    public function telegram(Request $request)
    {
        $telegram = new TelegramService();
        $data_request = $request->get('message',$request->channel_post);
        if (is_null($data_request)) {
            $telegram->sendMessage("Không phân tích được dữ liệu");
            return;
        }
        try {
            $content = $data_request['text'] ?? $data_request['caption'] ?? null;
            if (!$content) {
                return;
            }
            // Regex pattern để kiểm tra và tách chuỗi
            $pattern = "/\/(thu|chi)|(\d+)|([a-zA-Z0-9_,]+)|(\([^)]+\))/";
            preg_match_all($pattern, $content, $matches);
            $data = $matches[0];
            $amount = intval($data[1]);
            if (sizeof($data) > 4 || sizeof($data) < 2 || !$amount) {
                return;
            }
            $chat = new TelegramHistory();
            $chat->content = Str::limit($content,250);
            $chat->data_json = json_encode($request->all());
            $chat->save();

            // extract request content
            $labels = explode(',', ($data[2] ?? ''));
            $labels = Label::query()->whereIn('shorten', $labels)
                ->pluck('id')->toArray();

            $cash = new Cash();
            $cash->time = date('Y-m-d H:i:s');
            $cash->amount = $amount;
            $cash->note = rtrim(ltrim(($data[3] ?? null),'('),')');
            switch ($data[0]) {
                case "/thu" :
                    # /thu <amount> <label1,label2,...> (ghi chú)
                    $cash->type = Cash::$THU;
                    break;
                case "/chi" :
                    # /chi <amount> <label1,label2,...> (ghi chú)
                    $cash->type = Cash::$CHI;
                    break;
                default:
                    $telegram->sendMessage("Lệnh không tồn tại hoặc không đúng cú pháp. Tên thẻ hãy sử dụng các ký tự từ A-Z, 0-9, _ và ghi chú để dấu ngoặc đơn (...)");
                    exit();
            }
            $cash->save();
            $cash->labels()->sync($labels);
            $telegram->sendMessage("Ghi thành công");
        } catch (\Exception $exception) {
            $telegram->sendMessage("Lỗi không thể xử lý");
            Log::debug($exception->getMessage(),$request->all());
        }
    }

    public function getAdminConfig($key)
    {
        // Nếu key yêu cầu không nằm trong danh sách cho phép, từ chối truy cập.
        if (!in_array($key, $this->allowedConfigKeys)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $value = config($key);
        return response()->json(['key' => $key, 'value' => $value]);
    }













    

    /**
     * Handle webhook from Pos Pancake
     * Xử lý webhook từ Pancake để tạo mới hoặc cập nhật đơn hàng
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePosPancake(Request $request)
    {
        try {
            // Lấy toàn bộ payload JSON
            $payload = $request->all();
            
            // Log payload nhận được
            // Log::channel('pos_pancake')->info('Webhook received', [
            //     'timestamp' => now()->toDateTimeString(),
            //     'ip' => $request->ip(),
            //     'user_agent' => $request->userAgent(),
            //     'payload' => $payload
            // ]);

            // Kiểm tra type = orders
            if (!isset($payload['payload']['type']) || $payload['payload']['type'] !== 'orders') {
                return response()->json([
                    'status' => 'ignored',
                    'message' => 'Type is not orders, webhook ignored'
                ], 200);
            }

            $orderData = $payload['payload'];
            $eventType = $orderData['event_type'] ?? null;

            // Chỉ xử lý event_type = create hoặc update
            if (!in_array($eventType, ['create', 'update'])) {
                return response()->json([
                    'status' => 'ignored',

                    'message' => 'Event type not supported'
                ], 200);
            }

            // Xử lý dữ liệu đơn hàng
            $processedData = $this->processOrderData($orderData);
            
            if ($eventType === 'create') {
                // Tạo mới đơn hàng
                $this->createOrder($processedData);
                $message = 'Order created successfully';
            } else {
                // Cập nhật đơn hàng
                $this->updateOrder($processedData);
                $message = 'Order updated successfully';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'order_id' => $processedData['order_id']
            ], 200);

        } catch (\Exception $e) {
            // Log lỗi chi tiết
            Log::channel('pos_pancake')->error('Webhook processing error', [
                'timestamp' => now()->toDateTimeString(),
                'ip' => $request->ip(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Xử lý và chuẩn hóa dữ liệu đơn hàng từ payload
     *
     * @param array $orderData
     * @return array
     */
    private function processOrderData($orderData)
    {
        // Lấy thông tin địa chỉ shipping
        $shippingAddress = $orderData['shipping_address'] ?? [];
        
        // Lấy thông tin khách hàng
        $customer = $orderData['customer'] ?? [];
        $customerAddresses = $customer['shop_customer_addresses'] ?? [];
        
        // Lấy thông tin partner (shipment)
        $partner = $orderData['partner'] ?? [];
        $extendCode = $partner['extend_code'] ?? null;

        // Xử lý số điện thoại theo logic yêu cầu
        $customerPhone = $this->processPhoneNumber(
            $shippingAddress['phone_number'] ?? 
            ($customerAddresses[0]['phone_number'] ?? '')
        );

        // Xử lý thời gian - chuyển từ UTC sang +7
        $insertedAtUTC = $orderData['inserted_at'] ?? $orderData['updated_at'] ?? null;
        $insertedAt = $this->convertToLocalTime($insertedAtUTC);

        $timeSendPartnerUTC = $orderData['time_send_partner'] ?? null;
        $timeSendPartner = $this->convertToLocalTime($timeSendPartnerUTC);

        $posUpdatedAtUTC = $orderData['updated_at'] ?? null;
        $posUpdatedAt = $this->convertToLocalTime($posUpdatedAtUTC);

        return [
            'order_id' => $orderData['id'] ?? null,
            'system_id' => $orderData['system_id'] ?? null,
            'shipment_id' => $extendCode,
            'cod' => $orderData['cod'] ?? 0,
            'page_id' => $orderData['page_id'] ?? null,
            'order_link' => $orderData['order_link'] ?? null,
            'link_confirm_order' => $orderData['link_confirm_order'] ?? null,
            'customer_name' => $customerAddresses[0]['full_name'] ?? $shippingAddress['full_name'] ?? null,
            'customer_phone' => $customerPhone,
            'customer_id' => $customer['customer_id'] ?? null,
            'customer_fb_id' => $customer['fb_id'] ?? null,
            'customer_addresses' => $shippingAddress['address'] ?? null,
            'province_name' => $shippingAddress['province_name'] ?? null,
            'province_id' => $shippingAddress['province_id'] ?? null,
            'district_name' => $shippingAddress['district_name'] ?? null, 
            'district_id' => $shippingAddress['district_id'] ?? null,
            'commune_name' => $shippingAddress['commune_name'] ?? null, // Lưu ý: có thể là 'commnue_name' trong payload
            'commune_id' => $shippingAddress['commune_id'] ?? null,
            'status' => $orderData['status'] ?? 0,
            'sub_status' => $orderData['sub_status'] ?? null,
            'status_name' => $orderData['status_name'] ?? null,
            'post_id' => $orderData['post_id'] ?? null,
            'order_sources' => $orderData['order_sources'] ?? null,
            'order_sources_name' => $orderData['order_sources_name'] ?? null,
            'conversation_id' => $orderData['conversation_id'] ?? null,
            'total_quantity' => $orderData['total_quantity'] ?? 0,
            'items_length' => $orderData['items_length'] ?? 0,
            'time_send_partner' => $timeSendPartner,
            'pos_updated_at' => $posUpdatedAt,
            'inserted_at' => $insertedAt
        ];
    }

    /**
     * Xử lý số điện thoại theo logic yêu cầu
     * Validation phone String(phone_raw).split('/')[0].trim().replace(/^\+84/, '0').replace(/\D/g, '');
     *
     * @param string $phoneRaw
     * @return string|null
     */
    private function processPhoneNumber($phoneRaw)
    {
        if (empty($phoneRaw)) {
            return null;
        }

        // Chuyển sang string và xử lý theo logic
        $phone = (string) $phoneRaw;
        
        // Split by '/' và lấy phần đầu
        $phoneParts = explode('/', $phone);
        $phone = trim($phoneParts[0]);
        
        // Thay thế +84 bằng 0
        $phone = preg_replace('/^\+84/', '0', $phone);
        
        // Loại bỏ tất cả ký tự không phải số
        $phone = preg_replace('/\D/', '', $phone);

        return $phone ?: null;
    }

    /**
     * Chuyển đổi thời gian từ UTC sang múi giờ +7
     *
     * @param string|null $utcTime
     * @return string|null
     */
    private function convertToLocalTime($utcTime)
    {
        if (empty($utcTime)) {
            return null;
        }

        try {
            // Parse thời gian UTC
            $carbonTime = Carbon::parse($utcTime, 'UTC');
            
            // Chuyển sang múi giờ +7 (Asia/Ho_Chi_Minh)
            $localTime = $carbonTime->setTimezone('Asia/Ho_Chi_Minh');
            
            // Trả về định dạng yyyy-MM-dd HH:mm:ss
            return $localTime->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
            Log::warning('Failed to convert time', [
                'utc_time' => $utcTime,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Tạo mới đơn hàng
     *
     * @param array $data
     * @return void
     */
    private function createOrder($data)
    {
        PosOrder::create($data);
        
        Log::channel('pos_pancake')->info('Order created', [
            'order_id' => $data['order_id'],
            'customer_phone' => $data['customer_phone']
        ]);
    }

    /**
     * Cập nhật đơn hàng hiện có
     *
     * @param array $data
     * @return void
     */
    private function updateOrder($data)
    {
        $orderId = $data['order_id'];
        
        // Tìm đơn hàng theo order_id
        $existingOrder = PosOrder::where('order_id', $orderId)->first();
        
        if (!$existingOrder) {
            // Nếu không tìm thấy, tạo mới
            $this->createOrder($data);
            Log::channel('pos_pancake')->warning('Order not found for update, created new', [
                'order_id' => $orderId
            ]);
            return;
        }

        // Cập nhật đơn hàng
        $existingOrder->update($data);
        
        Log::channel('pos_pancake')->info('Order updated', [
            'order_id' => $orderId,
            'customer_phone' => $data['customer_phone']
        ]);
    }





}