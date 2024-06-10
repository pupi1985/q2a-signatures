<?php

function qa_get_permit_options()
{
    $permits = qa_get_permit_options_base();
    $permits[] = 'signature_allow';
    $permits[] = 'signature_edit_allow';

    return $permits;
}

function qa_get_request_content()
{
    $qa_content = qa_get_request_content_base();

    if (qa_request_part(0) !== 'user') {
        return $qa_content;
    }

    if (isset($qa_content['form_profile']['fields']['permits'])) {
        $ov = $qa_content['form_profile']['fields']['permits']['value'];
        $ov = str_replace('[profile/signature_allow]', qa_lang('signature_plugin/signature_allow'), $ov);
        $ov = str_replace('[profile/signature_edit_allow]', qa_lang('signature_plugin/signature_edit_allow'), $ov);
        $qa_content['form_profile']['fields']['permits']['value'] = $ov;
    }

    $userid = @$qa_content['raw']['userid'];
    if (!$userid) {
        return $qa_content;
    }

    $handles = qa_userids_to_handles(array($userid));
    $handle = $handles[$userid];

    $sectionTitle = sprintf('<span id="signature_text_title">%s</span>', qa_lang_html('signature_plugin/signature'));

    if ((qa_get_logged_in_handle() === $handle && !qa_user_permit_error('signature_allow')) || !qa_user_permit_error('signature_edit_allow')) {
        $ok = null;

        $editorName = qa_opt('signatures_format');

        $editor = qa_load_module('editor', $editorName);
        if ($editor === null) {
            $editor = qa_load_module('editor', '');
        }

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

        if (qa_clicked('signature_save')) {
            if (strlen(qa_post_text('signature_text')) > qa_opt('signatures_length')) {
                $error = 'Max possible signature length is 1000 characters';
            } else {
                qa_get_post_content('editor', 'signature_text', $newEditor, $readdata, $informat, $readDataText);

                qa_db_query_sub(
                    'INSERT INTO ^usersignatures (userid, signature, format) VALUES (#, $, $) ' .
                    'ON DUPLICATE KEY UPDATE signature = $, format = $',
                    $userid, $readdata, $informat, $readdata, $informat
                );
                $ok = qa_lang_html('users/profile_saved');
            }
        }

        $signatureRecord = getSignatureForUserId($userid);

        $fields['content'] = qa_editor_load_field($editor, $qa_content, $signatureRecord['signature'] ?? '', $signatureRecord['format'] ?? '', 'signature_text', 12, false);

        if (empty($editorName) || $editorName === 'Markdown Editor') {
            $fields['elCount'] = array(
                'label' => '<div id="elCount">' . qa_opt('signatures_length') . '</div>',
                'type' => 'static',
            );
        }

        $form = array(
            'ok' => ($ok && !isset($error)) ? $ok : null,

            'error' => @$error,

            'style' => 'tall',

            'title' => $sectionTitle,

            'tags' => 'action="' . qa_self_html() . '#signature_text_title" method="POST"',

            'fields' => $fields,

            'buttons' => array(
                array(
                    'label' => qa_lang_html('main/save_button'),
                    'tags' => 'name="signature_save"',
                ),
            ),

            'hidden' => array(
                'editor' => $editorName,
                'dosavesig' => '1',
            ),
        );
        $qa_content['user_signature_form'] = $form;
    } else if (qa_opt('signatures_profile_enable')) {
        $signatureRecord = getSignatureForUserId($userid);

        if (empty($signatureRecord['signature'])) {
            return $qa_content;
        }

        $informat = $signatureRecord['format'];

        global $options;

        $signature = qa_viewer_html($signatureRecord['signature'], $informat, array(
            'blockwordspreg' => @$options['blockwordspreg'],
            'showurllinks' => @$options['showurllinks'],
            'linksnewwindow' => @$options['linksnewwindow'],
        ));

        $qa_content['user_signature_form'] = array(
            'title' => $sectionTitle,
            'fields' => [
                'signature' => [
                    'type' => 'static',
                    'label' => qa_opt('signatures_header') . $signature . qa_opt('signatures_footer'),
                ],
            ],
            'style' => 'tall',
        );
    }

    return $qa_content;
}

/**
 * @param $userid
 *
 * @return array|null
 */
function getSignatureForUserId($userid)
{
    return qa_db_read_one_assoc(qa_db_query_sub(
        'SELECT signature, format FROM ^usersignatures WHERE userid = #',
        $userid
    ), true);
}
