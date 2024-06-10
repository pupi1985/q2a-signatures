<?php

class qa_signatures_admin
{
    function option_default($option)
    {
        // For QA_PERMIT_USERS, QA_PERMIT_ADMINS
        require_once QA_INCLUDE_DIR . 'app/options.php';

        switch ($option) {
            case 'signatures_length':
                return 1000;
            case 'signatures_header':
                return '<div class="signature">';
            case 'signatures_footer':
                return '</div>';
            case 'signatures_format':
                return '';
            case 'signature_allow':
                return QA_PERMIT_USERS;
            case 'signature_edit_allow':
                return QA_PERMIT_ADMINS;
            default:
                return null;
        }

    }

    function allow_template($template)
    {
        return $template !== 'admin';
    }

    function admin_form(&$qa_content)
    {
        $ok = null;

        if (qa_clicked('signatures_save')) {
            if (qa_post_text('signatures_length') > 1000) {
                $error = 'Max possible signature length is 1000 characters';
            } else {
                if (!qa_opt('signatures_enable') && qa_post_text('signatures_enable')) {
                    qa_db_query_sub(
                        'CREATE TABLE IF NOT EXISTS ^usersignatures (' .
                        'userid INT(11) NOT NULL,' .
                        'signature VARCHAR (1000) DEFAULT \'\',' .
                        'format VARCHAR (20) DEFAULT \'\',' .
                        'id INT(11) NOT NULL AUTO_INCREMENT,' .
                        'UNIQUE (userid),' .
                        'PRIMARY KEY (id)' .
                        ') ENGINE=MyISAM DEFAULT CHARSET=utf8'
                    );
                }
                qa_opt('signatures_enable', (bool)qa_post_text('signatures_enable'));
                qa_opt('signatures_q_enable', (bool)qa_post_text('signatures_q_enable'));
                qa_opt('signatures_a_enable', (bool)qa_post_text('signatures_a_enable'));
                qa_opt('signatures_c_enable', (bool)qa_post_text('signatures_c_enable'));
                qa_opt('signatures_profile_enable', (bool)qa_post_text('signatures_profile_enable'));
                qa_opt('signatures_length', (int)qa_post_text('signatures_length'));
                qa_opt('signatures_format', qa_post_text('signatures_format'));
                qa_opt('signatures_html', (bool)qa_post_text('signatures_html'));
                qa_opt('signatures_header', qa_post_text('signatures_header'));
                qa_opt('signatures_footer', qa_post_text('signatures_footer'));
                $ok = 'Settings Saved.';
            }
        }

        // Create the form for display

        $rawEditors = qa_list_modules('editor');
        $editors = [];
        foreach ($rawEditors as $editorName) {
            $editors[$editorName] = empty($editorName) ? qa_lang_html('admin/basic_editor') : $editorName;
        }

        $fields = array();

        $fields[] = array(
            'label' => 'Enable signatures',
            'tags' => 'NAME="signatures_enable"',
            'value' => qa_opt('signatures_enable'),
            'type' => 'checkbox',
        );

        $fields[] = array(
            'label' => 'in questions',
            'tags' => 'NAME="signatures_q_enable"',
            'value' => qa_opt('signatures_q_enable'),
            'type' => 'checkbox',
        );

        $fields[] = array(
            'label' => 'in answers',
            'tags' => 'NAME="signatures_a_enable"',
            'value' => qa_opt('signatures_a_enable'),
            'type' => 'checkbox',
        );

        $fields[] = array(
            'label' => 'in comments',
            'tags' => 'NAME="signatures_c_enable"',
            'value' => qa_opt('signatures_c_enable'),
            'type' => 'checkbox',
        );

        $fields[] = array(
            'type' => 'blank',
        );

        $fields[] = array(
            'label' => 'Show signatures in public profiles',
            'tags' => 'NAME="signatures_profile_enable"',
            'value' => qa_opt('signatures_profile_enable'),
            'type' => 'checkbox',
        );
        $fields[] = array(
            'label' => 'Signature length (chars)',
            'type' => 'number',
            'value' => (int)qa_opt('signatures_length'),
            'tags' => 'NAME="signatures_length"',
            'note' => 'max possible is 1000 characters',
        );
        $fields[] = array(
            'label' => 'Signature format',
            'tags' => 'NAME="signatures_format"',
            'type' => 'select',
            'options' => $editors,
            'value' => $editors[qa_opt('signatures_format')] ?? '',
        );

        $fields[] = array(
            'label' => 'Allow HTML',
            'tags' => 'NAME="signatures_html"',
            'value' => qa_opt('signatures_html'),
            'type' => 'checkbox',
        );
        $fields[] = array(
            'type' => 'blank',
        );

        $fields[] = array(
            'label' => 'Signature header',
            'type' => 'text',
            'value' => qa_html(qa_opt('signatures_header')),
            'tags' => 'NAME="signatures_header"',
        );

        $fields[] = array(
            'label' => 'Signature footer',
            'type' => 'text',
            'value' => qa_html(qa_opt('signatures_footer')),
            'tags' => 'NAME="signatures_footer"',
        );

        return array(
            'ok' => ($ok && !isset($error)) ? $ok : null,

            'fields' => $fields,

            'buttons' => array(
                array(
                    'label' => 'Save',
                    'tags' => 'NAME="signatures_save"',
                ),
            ),
        );
    }
}
