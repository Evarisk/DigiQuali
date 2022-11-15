-- 1.1.0
ALTER TABLE `llx_dolismq_controldet` ADD `answer_photo` TEXT NOT NULL AFTER `answer`;

-- 1.2.0
ALTER TABLE `llx_dolismq_control` ADD `note_public` TEXT NULL AFTER `status`;
ALTER TABLE `llx_dolismq_control` ADD `note_private` TEXT NULL AFTER `note_public`;
ALTER TABLE `llx_dolismq_control` DROP `fk_product`;
ALTER TABLE `llx_dolismq_control` DROP `fk_lot`;
ALTER TABLE `llx_dolismq_control` DROP `fk_soc`;
ALTER TABLE `llx_dolismq_control` DROP `fk_project`;
ALTER TABLE `llx_dolismq_control` DROP `fk_task`;

-- 1.3.0
ALTER TABLE `llx_dolismq_sheet` ADD `element_linked` TEXT NULL AFTER `label`;

ALTER TABLE `llx_dolismq_question` ADD `show_photo` BOOLEAN NULL AFTER `description`;
ALTER TABLE `llx_dolismq_question` ADD `authorize_answer_photo` BOOLEAN NULL AFTER `show_photo`;
ALTER TABLE `llx_dolismq_question` ADD `enter_comment` BOOLEAN NULL AFTER `authorize_answer_photo`;

ALTER TABLE `llx_dolismq_control` ADD `fk_project` INTEGER NULL AFTER `fk_user_controller`;

-- 1.4.0

ALTER TABLE `llx_element_element` ADD `position` INTEGER;
UPDATE `llx_element_element` SET `sourcetype` = 'dolismq_question' WHERE `sourcetype` = 'question';
UPDATE `llx_element_element` SET `sourcetype` = 'dolismq_sheet' WHERE `sourcetype` = 'sheet';
UPDATE `llx_element_element` SET `sourcetype` = 'dolismq_control' WHERE `sourcetype` = 'control';
UPDATE `llx_element_element` SET `targettype` = 'dolismq_question' WHERE `targettype` = 'question';
UPDATE `llx_element_element` SET `targettype` = 'dolismq_sheet' WHERE `targettype` = 'sheet';
UPDATE `llx_element_element` SET `targettype` = 'dolismq_control' WHERE `targettype` = 'control';
