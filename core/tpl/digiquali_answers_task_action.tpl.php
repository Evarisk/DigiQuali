<?php

/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/digiquali_answers_task_action.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers save action
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Parameters : $action
 * Objects    : $object, $objectLine, $sheet
 */

if ($action == 'add_task' && $permissiontoaddtask) {

    $data = json_decode(file_get_contents('php://input'), true);

    $task->ref        = $refTaskMod->getNextValue(null, $task);
    $task->label      = $data['label'];
    $task->fk_project = $data['fk_project'] ?? null;
    $task->datec     = dol_now();
    if (!empty($data['date_start'])) {
        $task->date_start = dol_stringtotime(['date_start']);
    } else {
        $task->date_start = dol_now('tzuser');
    }
    if (!empty($data['date_end'])) {
        $task->date_end = dol_stringtotime($data['date_end']);
    }
    $task->budget_amount  = $data['budget_amount'] ?? null;
    $task->fk_task_parent = 0;

    $result = $task->create($user);
    if ($result < 0) {
        // @todo manage error
    } else {
        $task->add_object_linked($objectLine->element, $data['line_id']);

        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
    }
    $action = '';
}

if ($action == 'update_task' && $permissiontoaddtask) {
    $taskId = GETPOST('task_id', 'int');

    $data = json_decode(file_get_contents('php://input'), true);

    $task->fetch($taskId);
    $task->label = $data['label'];
    if (!empty($data['date_start'])) {
        $task->date_start = dol_stringtotime($data['date_start']);
    } else {
        $task->date_start = dol_now('tzuser');
    }
    if (!empty($data['date_end'])) {
        $task->date_end = dol_stringtotime($data['date_end']);
    }
    $task->budget_amount  = $data['budget_amount'] ?? null;
    $task->progress       = $data['progress'] ?? 0;

    $result = $task->update($user);
    if ($result < 0) {
        // @todo manage error
    } else {
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
    }
    $action = '';
}

if ($action == 'delete_task' && $permissiontoaddtask) {
    $taskId = GETPOST('task_id', 'int');

    $task->fetch($taskId);
    $result = $task->delete($user);

    if ($result > 0) {
        $task->deleteObjectLinked(null, $objectLine->element, $taskId, $task->element);
        // Delete task OK
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } else {
        // Delete task KO
        header('HTTP/1.1 500 Internal Server');
        die(json_encode(array('message' => $langs->transnoentities($task->error), 'code' => '1337')));
    }
}

if ($action == 'check_task' && $permissiontoaddtask) {
    $taskId = GETPOST('task_id', 'int');

    $task->fetch($taskId);
    if ($task->progress == 0) {
        $task->progress = 100;
    } else {
        $task->progress = 0;
    }
    $result = $task->update($user);

    if ($result > 0) {
        // Update task OK
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } else {
        // Update task KO
        header('HTTP/1.1 500 Internal Server');
        die(json_encode(array('message' => $langs->transnoentities($task->error), 'code' => '1337')));
    }
}

if ($action == 'add_task_timespend' && $permissiontoadd) {
    $data = json_decode(file_get_contents('php://input'), true);

    $date       = $data['date'];
    $hour       = $data['hour'];
    $min        = $data['min'];
    $comment    = $data['comment'];
    $time_spent = $data['time_spent'];

    $task->fetch(GETPOST('task_id', 'int'));

    if (!empty($date)) {
        $task->timespent_date = strtotime(preg_replace('/\//', '-', $date));
        $task->timespent_date = dol_time_plus_duree($task->timespent_date, $hour, 'h');
        $task->timespent_date = dol_time_plus_duree($task->timespent_date, $min, 'i');
    } else {
        $task->timespent_date = dol_now('tzuser');
    }
    $task->timespent_note     = $comment;
    $task->timespent_duration = $time_spent * 60;
    $task->timespent_fk_user  = $user->id;

    $result = $task->addTimeSpent($user);

    if ($result > 0) {
        // Creation task time spent OK
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } else {
        // Creation task time spent KO
        if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
        else setEventMessages($task->error, null, 'errors');
    }
}

if ('delete_task_timespent' === $action && $permissiontoadd) {
    $timeSpendId = GETPOST('timespent_id', 'int');

    $task->fetchTimeSpent($timeSpendId);
    $task->fetch($task->id);

    $result = $task->delTimeSpent($user, false);

    if ($result > 0) {
        // Delete task time spent OK
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } else {
        // Delete task time spent KO
        if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
        else setEventMessages($task->error, null, 'errors');
    }
}


