<?php

namespace App\Admin\Controllers;

use App\Models\Cash;
use App\Models\Label;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class CashFlowController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Thu / chi';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Cash());
        $grid = $grid->with(['labels']);
        // $grid->model()->orderBy('id', 'desc');
        $grid->model()->orderBy('time', 'desc');
        $labels = Label::query()->orderBy('id', 'desc')->get(['id','name']);

        // column id
        $grid->column('id', __('ID'))->sortable();
        // end column id
        // column type
        $grid->column('type', __('Loại'))->sortable()
            ->display(function ($type) {
                return $type ?  "Chi" : "Thu";
            })
            ->label(['success','danger'])
            ->filter(['Thu','Chi']);
        // end column type
        // column amount
        $grid->column('amount', __('Số tiền'))
            ->display(function ($amount) {
                return number_format($amount) . ' đ';
            })
            ->totalRow(function ($total) {
                return number_format($total) . ' đ';
            });
        // end column amount
        // column labels
        $grid->column('labels', __('Thẻ'))
            ->view('partials.select_labels')
            ->width(350);
        // end column labels
        // column note
        $grid->column('note', __('Ghi chú'))
            ->view('partials.note')
            ->width(350);
        // end column note
        // column time
        $grid->column('time', __('Thời gian'))
            ->sortable()
            ->filter('range', 'datetime')
            ->width(140);
        // and column time
        // column created_at
        $grid->column('created_at', __('Tạo lúc'))
            ->display(function ($created_at) {
                return date("Y-m-d H:i:s", strtotime($created_at));
            })
            ->filter('range', 'datetime')
            ->width(120);
        // end column created_at
            
        // column updated_at
        // $grid->column('updated_at', __('Cập nhật lần cuối'))
        //     ->display(function ($updated_at) {
        //         return date("Y-m-d H:i:s", strtotime($updated_at));
        //     })
        //     ->width(120);
        // end column updated_at

        // region filter
        $grid->filter(function($filter) use ($labels){
            $filter->in('labels.id', __('Thẻ'))->multipleSelect($labels->mapWithKeys(function ($label) {
                return [$label->id => $label->name];
            }));

            $filter->where(function ($query) {
                $query->where('type', $this->input);
            }, __('Loại'))->radio([0 => 'Thu', 1 => 'Chi']);

            $filter->between('time', __('Thời gian'))->date();
        });
        // endregion filter

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Cash::findOrFail($id));

        $show->field('type', __('Loại'))
            ->as(function ($type) {
                return $type ? "Chi" : "Thu";
            });
        $show->field('amount', __('Số tiền'));
        $show->field('time', __('Thời gian'));
        $show->labels(__('Thẻ'))->as(function ($labels) {
            $badges = [];
            foreach ($labels as $label) {
                $badges[] = $label->name;
            }
            return implode(', ',$badges);
        });
        $show->note()->unescape()->as(function ($note) {

            return $note;

        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Cash());
        $labels = Label::query()->orderBy('id', 'desc')->get(['id','name']);

        $form->select('type', __('Loại'))
            ->options(['thu','chi'])->required();
        $form->number('amount', __('Số tiền'))->min(1)->required();
        $form->datetime('time', __('Thời gian'))->required();
        $form->multipleSelect('labels', __('Thẻ'))->options($labels->mapWithKeys(function ($label) {
            return [$label->id => $label->name];
        }));
        // $form->ckeditor('note');
        $form->text('note');

        return $form;
    }

    public function custom_update(Request $request, $id)
    {
        $response = [
            "success" => false,
            "message" => ''
        ];

        $cash = Cash::query()->find($id);
        if (!$cash) {
            $response['message'] = "Account not found";
            return response()->json($response);
        }
        if ($request->exists("note")) {
            $cash->note = $request->get("note");
        }

        if ($request->exists("labels")) {
            $cash->labels()->sync($request->get("labels",[]));
        }

        $cash->save();

        $response['success'] = true;
        return response()->json($response);
    }
}
