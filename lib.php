<?php
// This file is part of Moodle - http://moodle.org/
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
 * Callback functions for the User titles plugin.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a title node to the Moodle user profile.
 *
 * @param \core_user\output\myprofile\tree $tree Profile tree.
 * @param stdClass $user User whose profile is displayed.
 * @param bool $iscurrentuser Whether the profile belongs to the current user.
 * @param stdClass|null $course Course context, if any.
 * @return bool
 */
function local_usertitles_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    $user,
    $iscurrentuser,
    $course
): bool {
    if (isguestuser($user)) {
        return false;
    }

    $title = \local_usertitles\manager::get_user_title((int) $user->id);
    if (!$title) {
        return false;
    }

    $category = new \core_user\output\myprofile\category(
        'local_usertitles',
        get_string('profilecategory', 'local_usertitles')
    );
    $tree->add_category($category);

    $url = null;
    if (local_usertitles_can_edit_user_title($user)) {
        $url = new moodle_url('/local/usertitles/assign.php', ['userid' => $user->id]);
    }

    $content = s(\local_usertitles\manager::format_name($user));
    $node = new \core_user\output\myprofile\node(
        'local_usertitles',
        'assignedtitle',
        get_string('profiletitle', 'local_usertitles'),
        null,
        $url,
        $content
    );
    $tree->add_node($node);

    return true;
}

/**
 * Adds a title selector link to user settings.
 *
 * @param navigation_node $parentnode Parent navigation node.
 * @param stdClass $user User whose settings are displayed.
 * @param context_user $context User context.
 * @param stdClass|null $course Course, if any.
 * @param context_course|null $coursecontext Course context, if any.
 * @return void
 */
function local_usertitles_extend_navigation_user_settings(
    $parentnode,
    $user,
    $context,
    $course,
    $coursecontext
): void {
    if (isguestuser($user) || !local_usertitles_can_edit_user_title($user)) {
        return;
    }

    $url = new moodle_url('/local/usertitles/assign.php', ['userid' => $user->id]);
    $parentnode->add(
        get_string('usertitle', 'local_usertitles'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_usertitles'
    );
}

/**
 * Adds an assignment link to the navigation for a user profile.
 *
 * @param navigation_node $parentnode Parent navigation node.
 * @param stdClass $user User whose profile is displayed.
 * @param context_user $context User context.
 * @param stdClass|null $course Course, if any.
 * @param context_course|null $coursecontext Course context, if any.
 * @return void
 */
function local_usertitles_extend_navigation_user(
    $parentnode,
    $user,
    $context,
    $course,
    $coursecontext
): void {
    if (isguestuser($user) || !local_usertitles_can_edit_user_title($user)) {
        return;
    }

    $url = new moodle_url('/local/usertitles/assign.php', ['userid' => $user->id]);
    $parentnode->add(
        get_string('assignusertitle', 'local_usertitles'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_usertitles_assign'
    );
}

/**
 * Determines whether the current user may edit a user's title.
 *
 * @param stdClass $user Target user.
 * @return bool
 */
function local_usertitles_can_edit_user_title(stdClass $user): bool {
    global $USER;

    $context = context_user::instance((int) $user->id);
    if (has_capability('local/usertitles:assignusertitles', $context)) {
        return true;
    }

    return (int) $USER->id === (int) $user->id
        && (bool) get_config('local_usertitles', 'allowselfselection')
        && has_capability('local/usertitles:selectowntitle', $context);
}

/**
 * Public compatibility helper for integrations.
 *
 * New integrations may call \local_usertitles\manager::format_name() directly.
 *
 * @param stdClass|int $user User record or user id.
 * @param bool $includetitle Whether to include the assigned title.
 * @return string
 */
function local_usertitles_format_name($user, bool $includetitle = true): string {
    return \local_usertitles\manager::format_name($user, $includetitle);
}
