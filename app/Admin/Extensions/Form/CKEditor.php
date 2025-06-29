<?php

namespace App\Admin\Extensions\Form;

use Encore\Admin\Form\Field;

class CKEditor extends Field
{
    public static $js = [
        'https://cdn.ckeditor.com/4.6.2/standard-all/ckeditor.js',
    ];

    protected $view = 'partials.edit_note';

    public function render()
    {
        $this->script = "
            CKEDITOR.plugins.addExternal( 'pasteUploadImage', '/assets/plugins/ckeditor/pasteUploadImage/', 'plugin.js' );
            CKEDITOR.replace('{$this->id}', {
            height: 500,
            width: '100%',
            extraPlugins: 'pasteUploadImage',
            customConfig: '/assets/plugins/ckeditor/config.js',
            // Use named CKFinder browser route
            filebrowserBrowseUrl: '" . route('ckfinder_browser') . "',
            // Use named CKFinder connector route
            filebrowserUploadUrl: '" . route('ckfinder_connector') . "?command=QuickUpload&type=Files'
            } );
        ";
        return parent::render();
    }
}
