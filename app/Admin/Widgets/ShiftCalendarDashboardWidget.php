<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Box;

/**
 * Class ShiftCalendarDashboardWidget.
 *
 * @package App\Admin\Widgets
 */
class ShiftCalendarDashboardWidget extends Box
{
    /**
     * @var string
     */
    protected $view = 'admin.widgets.shift_calendar_dashboard';

    /**
     * @var string
     */
    protected $title = 'Lịch làm việc';

    /**
     * Widget constructor.
     */
    public function __construct()
    {
        // Gán tiêu đề và view cho widget box
        parent::__construct($this->title, view($this->view));
    }
}
