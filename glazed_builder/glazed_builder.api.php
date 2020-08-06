<?php

/**
 * @file
 * Hooks provided by the Glazed Builder module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to modify Glazed Builder utility classes list.
 *
 * You can also extend classes in a theme's info file
 * @see http://www.sooperthemes.com/documentation/extend-carbide-builder-utility-classes
 *
 * @param array $glazed_classes
 *   Array of classes with one or more classes as key (spaces are allowed) and
 *   a description of the class as value. You can start a new option group in
 *   the classes selectbox by adding an item with key optgroup-yourname and a
 *   description as value.
 */
function hook_glazed_builder_classes(&$glazed_classes) {
  $glazed_classes['optgroup-my-group'] = t('My Option Group');
  $glazed_classes['my-class'] = t('My label');
}

/**
 * Allows modules to modify list of folders containing Glazed Builder button styles
 *
 * This function looks for .html files in the list of paths and then searches for
 * class attributes containing a bootstrap button class "btn" and extracts the rest
 * of the classes in class attribute to define the button style.
 *
 * @param array $glazed_buttons_folders
 *   Array of paths pointing to folders that contain HTML files describing buttons
 *
 * @see glazed_elements/Buttons
 *
 */
function hook_glazed_builder_buttons_folders(&$glazed_buttons_folders) {
  $glazed_buttons_folders[] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'glazed_elements/Buttons';
}

/**
 * @} End of "addtogroup hooks".
 */
