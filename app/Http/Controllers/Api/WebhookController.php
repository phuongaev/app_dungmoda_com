<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Cash;
use App\Models\Label;
use App\Models\TelegramHistory;
use App\Services\TelegramService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     *
     * @param Request $request
     * @return Response
     */
    public function handlePosPancake(Request $request)
    {
        try {
            // Lấy toàn bộ payload JSON
            $payload = $request->all();
            
            // Tạo log entry với timestamp và payload
            $logData = [
                'timestamp' => now()->toDateTimeString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload
            ];
            
            // Ghi log vào file chuyên dụng cho Pos Pancake
            Log::channel('pos_pancake')->info('Webhook received', $logData);
            
            // Trả về response thành công
            return response()->json([
                'status' => 'success',
                'message' => 'Webhook received successfully',
                'timestamp' => now()->toDateTimeString()
            ], 200);
            
        } catch (\Exception $e) {
            // Log lỗi nếu có
            Log::channel('pos_pancake')->error('Webhook error: ' . $e->getMessage(), [
                'timestamp' => now()->toDateTimeString(),
                'ip' => $request->ip(),
                'payload' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

}
