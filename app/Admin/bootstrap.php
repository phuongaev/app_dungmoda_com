<?php

Use Encore\Admin\Admin;
Use Encore\Admin\Form;
Use App\Admin\Extensions\Form\CKEditor;

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

Form::forget(['map', 'editor']);
// Form::extend('ckeditor', CKEditor::class);
Admin::css('/assets/css/main.css');
// Admin::js('/assets/js/app.js');
// Admin::js('/assets/js/label.js');
