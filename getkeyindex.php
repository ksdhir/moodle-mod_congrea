<?php
// This file is part of Moodle - http://vidyamantra.com/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Authentication key
 *
 * @package    mod_congrea
 * @copyright  2020 Manisha
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('key_form.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_url(new moodle_url('/mod/congrea/getkeyindex.php'));

$PAGE->set_pagelayout('standard');
$PAGE->set_title('Congrea Plan');
$PAGE->set_heading('Get Congrea free plan');

echo $OUTPUT->header();

$mform = new mod_congrea_key_form(null, array('email' => $USER->email, 'firstname' => $USER->firstname ,
'lastname' => $USER->lastname , 'domain' => $CFG->wwwroot));

$configkey = get_config('mod_congrea', 'cgapi');
$configsecret = get_config('mod_congrea', 'cgsecretpassword');

if ($fromform = $mform->get_data()) {
    if ($configkey && $configsecret) {
        displaykeys($configkey, $configsecret, 'alreadyhave');
    } else {
        $postdata = array(
            'firstname' => $fromform->firstname,
            'lastname' => $fromform->lastname,
            'email' => $fromform->email,
            'domain' => $fromform->domain,
            'datacenter' => $fromform->datacenter
        );
        $request = json_encode($postdata);
        $serverurl = 'https://www.vidyamantra.com/portal/getvmkey.php?data=' . $request;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $serverurl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0 );
        $response = curl_exec($curl);
        if (!$response) {
            print "curl error " . curl_errno($curl ) . PHP_EOL;
        } else {
            function jsonp_decode($jsonp, $assoc = false) {
                if ($jsonp[0] !== '[' && $jsonp[0] !== '{') {
                    $jsonp = substr($jsonp, strpos($jsonp, '('));
                }
                return json_decode(trim($jsonp, '();'), $assoc);
            }
            $output = jsonp_decode($response, false);
            $key = $output->key;
            $secret = $output->secret;
            $error = $output->error;
            curl_close($curl);
        }
        if ($key && $secret) {
            if (!set_config('cgapi', $key, 'mod_congrea')) {
                echo $OUTPUT->error_text(get_string('keynotsaved', 'mod_congrea'));
            }
            if (!set_config('cgsecretpassword', $secret, 'mod_congrea')) {
                echo $OUTPUT->error_text(get_string('keynotsaved', 'mod_congrea'));
            }
            displaykeys($key, $secret, 'configuredheading');
        }
    }
} else {
    if ($configkey && $configsecret) {
        displaykeys($configkey, $configsecret, 'alreadyhave');
    } else {
        echo $OUTPUT->box(get_string('message', 'congrea'), "generalbox center clearfix");
        $mform->display();
    }
}
echo $OUTPUT->footer();

function displaykeys($k, $s, $c) {
    echo html_writer::tag('h4', get_string($c, 'congrea'));
    echo html_writer::tag('p', get_string('keyis', 'congrea') . $k);
    echo html_writer::tag('p', get_string('secretis', 'congrea') . $s);
}