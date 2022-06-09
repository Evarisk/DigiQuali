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

