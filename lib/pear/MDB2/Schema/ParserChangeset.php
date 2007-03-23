<?php
/*
+---------------------------------------------------------------------------+
| Max Media Manager v0.3                                                    |
| =================                                                         |
|                                                                           |
| Copyright (c) 2003-2006 m3 Media Services Limited                         |
| For contact details, see: http://www.m3.net/                              |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
/**
 *
 * $Id$
 */


require_once 'XML/Parser.php';
require_once 'MDB2/Schema/Validate.php';

if (empty($GLOBALS['_MDB2_Schema_Reserved'])) {
    $GLOBALS['_MDB2_Schema_Reserved'] = array();
}

/**
 * Parses an XML schema file
 *
 * @package MDB2_Schema
 * @category changeset
 * @access protected
 * @author
 */
class MDB2_Changeset_Parser extends XML_Parser
{
    var $instructionset = array('name'=>'','version'=>'', 'comments'=>'', 'constructive'=>array(),'destructive'=>array());

    var $constructive_changeset_definition = array('name'=>'','version'=>'', 'tables' => array());
    var $destructive_changeset_definition = array('name'=>'','version'=>'', 'tables' => array());
    var $test;

    var $events = array();
    var $hooks = array();
    var $fieldmap = array();

    var $name;
    var $version;

    var $elements = array();
    var $element = '';
    var $count = 0;

    var $remove = array();
    var $add = array();
    var $change = array();

    var $table = array();
    var $table_name = '';
    var $field = array();
    var $field_name = '';
    var $index = array();
    var $index_name = '';
    var $var_mode = false;
    var $variables = array();
    var $error;
    var $structure = false;
    var $val;

    function __construct($variables, $fail_on_invalid_names = true, $structure = false, $valid_types = array(), $force_defaults = true)
    {
        // force ISO-8859-1 due to different defaults for PHP4 and PHP5
        // todo: this probably needs to be investigated some more andcleaned up
        parent::XML_Parser('ISO-8859-1');
        $this->variables = $variables;
        $this->structure = $structure;
//        $this->val =& new MDB2_Schema_Validate($fail_on_invalid_names, $valid_types, $force_defaults);
    }

    function MDB2_Changeset_Parser($variables, $fail_on_invalid_names = true, $structure = false, $valid_types = array(), $force_defaults = true)
    {
        $this->__construct($variables, $fail_on_invalid_names, $structure, $valid_types, $force_defaults);
    }

    function startHandler($xp, $element, $attribs)
    {
        if (strtolower($element) == 'variable') {
            $this->var_mode = true;
            return;
        }

        $this->elements[$this->count++] = strtolower($element);
        $this->element = implode('-', $this->elements);

        switch ($this->element)
        {
            case 'instructionset':
                $this->hooks['constructive'] = array('tables'=>array());
                $this->hooks['destructive'] = array('tables'=>array());
                $this->events['constructive'] = array('tables'=>array());
                $this->events['destructive'] = array('tables'=>array());
            	break;
            case 'instructionset-name':
                $this->name = '';
            	break;
            case 'instructionset-version':
                $this->version = '';
            	break;
            case 'instructionset-constructive':
            	break;
            case 'instructionset-constructive-changeset':
            	break;
            case 'instructionset-constructive-changeset-name':
                $this->name = '';
            	break;
            case 'instructionset-constructive-changeset-version':
                $this->version = '';
            	break;
            case 'instructionset-constructive-changeset-add':
                $this->add = array();
            	break;
            case 'instructionset-constructive-changeset-add-table':
                $this->table_name = '';
                $this->table    = array();
            	break;
            case 'instructionset-constructive-changeset-change':
                $this->change = array();
            	break;
            case 'instructionset-constructive-changeset-change-table':
                $this->table = array();
            	break;
            case 'instructionset-constructive-changeset-change-table-name':
                $this->table_name = '';
            	break;
            case 'instructionset-constructive-changeset-change-table-add':
                $this->add = array();
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field':
                $this->field = array();
                $this->field_name = '';
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-name':
            case 'instructionset-constructive-changeset-change-table-add-field-type':
            case 'instructionset-constructive-changeset-change-table-add-field-notnull':
            case 'instructionset-constructive-changeset-change-table-add-field-default':
            case 'instructionset-constructive-changeset-change-table-add-field-was':
                break;
            case 'instructionset-constructive-changeset-change-table-change':
                $this->change = array();
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field':
                $this->field = array();
                $this->field_name = '';
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-name':
            case 'instructionset-constructive-changeset-change-table-change-field-type':
            case 'instructionset-constructive-changeset-change-table-change-field-notnull':
            case 'instructionset-constructive-changeset-change-table-change-field-default':
            case 'instructionset-constructive-changeset-change-table-change-field-was':
                break;
            case 'instructionset-constructive-changeset-change-table-index':
                break;
            case 'instructionset-constructive-changeset-change-table-index-add':
                $this->index_name = '';
                $this->index    = array();
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield':
                $this->field_name = '';
                $this->field = array();
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-name':
            case 'instructionset-constructive-changeset-change-table-index-add-primary':
            case 'instructionset-constructive-changeset-change-table-index-add-unique':
            case 'instructionset-constructive-changeset-change-table-index-add-was':
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-name':
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-sorting':
                break;

            case 'instructionset-destructive':
            	break;
            case 'instructionset-destructive-changeset':
            	break;
            case 'instructionset-destructive-changeset-name':
                $this->name = '';
            	break;
            case 'instructionset-destructive-changeset-version':
                $this->version = '';
            	break;
            case 'instructionset-destructive-changeset-remove':
            	break;
            case 'instructionset-destructive-changeset-change':
            	break;
            case 'instructionset-destructive-changeset-change-table':
                $this->table_name = '';
                $this->table    = array();
            	break;
            case 'instructionset-destructive-changeset-change-table-name':
            	break;
            case 'instructionset-destructive-changeset-change-table-remove':
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field':
                $this->field = array();
                $this->field_name = '';
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field-name':
            	break;
            case 'instructionset-destructive-changeset-table-index':
                break;
            case 'instructionset-destructive-changeset-change-table-index':
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove':
                $this->index_name = '';
                $this->index    = array();
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove-name':
                break;


        }
    }

    function endHandler($xp, $element)
    {
        if (strtolower($element) == 'variable') {
            $this->var_mode = false;
            return;
        }
        switch ($this->element)
        {
            case 'instructionset':
                $this->instructionset['events'] = $this->events;
                $this->instructionset['hooks'] = $this->hooks;
                $this->instructionset['test'] = $this->test;
                $this->instructionset['fieldmap'] = $this->fieldmap;
            	break;
            case 'instructionset-name':
                $this->instructionset['name'] = $this->name;
            	break;
            case 'instructionset-version':
                $this->instructionset['version'] = $this->version;
            	break;
            case 'instructionset-constructive':
                $this->instructionset['constructive'] = $this->constructive_changeset_definition;
            	break;
            case 'instructionset-constructive-changeset':
            	break;
            case 'instructionset-constructive-changeset-name':
                $this->constructive_changeset_definition['name'] = $this->name;
            	break;
            case 'instructionset-constructive-changeset-version':
                $this->constructive_changeset_definition['version'] = $this->version;
            	break;
            case 'instructionset-constructive-changeset-add':
            	break;
            case 'instructionset-constructive-changeset-add-table':
                if (!isset($this->constructive_changeset_definition['tables']['add'][$this->table_name]))
                {
                    $this->constructive_changeset_definition['tables']['add'][$this->table_name] = true;
                    $this->hooks['constructive']['tables'][$this->table_name]['self']['beforeAddTable'] = "beforeAddTable__{$this->table_name}";
                    $this->events['constructive']['tables'][$this->table_name]['self']['doAddTable']    = "doAddTable__{$this->table_name}";
                    $this->hooks['constructive']['tables'][$this->table_name]['self']['afterAddTable']  = "afterAddTable__{$this->table_name}";
                    //$this->map['tables'][$this->table_name]['was']  = "";
                }
            	break;
            case 'instructionset-constructive-changeset-change':
            	break;
            case 'instructionset-constructive-changeset-change-table':
            	break;
            case 'instructionset-constructive-changeset-change-table-name':
                if (!isset($this->constructive_changeset_definition['tables']['change'][$this->table_name]))
                {
                    $this->constructive_changeset_definition['tables']['change'][$this->table_name] = array();
//                    $this->events['tables'][$this->table_name]['fields']  = array();
//                    $this->events['tables'][$this->table_name]['indexes']  = array();
//                    $this->events['tables'][$this->table_name]['self']  = array();
                }
            	break;
            case 'instructionset-constructive-changeset-change-table-add':
                $this->constructive_changeset_definition['tables']['change'][$this->table_name]['add'] = $this->add;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field':
                $this->add['fields'][$this->field_name] = $this->field;
                $this->hooks['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['beforeAddField'] = "beforeAddField__{$this->table_name}__{$this->field_name}";
                $this->events['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['doAddField']    = "doAddField__{$this->table_name}__{$this->field_name}";
                $this->hooks['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['afterAddField']  = "afterAddField__{$this->table_name}__{$this->field_name}";
                $this->fieldmap[] = array('toTable'=>$this->table_name,'toField'=>$this->field_name, 'fromTable'=>$this->table_name, 'fromField'=>$this->field['was']);
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-name':
            case 'instructionset-constructive-changeset-change-table-add-field-type':
            case 'instructionset-constructive-changeset-change-table-add-field-notnull':
            case 'instructionset-constructive-changeset-change-table-add-field-default':
            case 'instructionset-constructive-changeset-change-table-add-field-was':
            	break;
            case 'instructionset-constructive-changeset-change-table-change':
                $this->constructive_changeset_definition['tables']['change'][$this->table_name]['change'] = $this->change;
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field':
                $this->change['fields'][$this->field_name] = $this->field;
                $this->hooks['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['beforeAlterField'] = "beforeAlterField__{$this->table_name}__{$this->field_name}";
                $this->events['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['doAlterField']    = "doAlterField__{$this->table_name}__{$this->field_name}";
                $this->hooks['constructive']['tables'][$this->table_name]['fields'][$this->field_name]['afterAlterField']  = "afterAlterField__{$this->table_name}__{$this->field_name}";
                //$this->events['tables'][$this->table_name]['fields'][$this->field_name]['was']  = $this->field['was'];
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-name':
            case 'instructionset-constructive-changeset-change-table-change-field-type':
            case 'instructionset-constructive-changeset-change-table-change-field-notnull':
            case 'instructionset-constructive-changeset-change-table-change-field-default':
            case 'instructionset-constructive-changeset-change-table-change-field-was':
            	break;
            case 'instructionset-constructive-changeset-change-table-index':
                break;
            case 'instructionset-constructive-changeset-change-table-index-add':
                $this->constructive_changeset_definition['tables']['change'][$this->table_name]['indexes']['add'][$this->index_name] = $this->index;
                $this->hooks['constructive']['tables'][$this->table_name]['indexes'][$this->index_name]['beforeAddIndex']  = "beforeAddIndex__{$this->table_name}__{$this->index_name}";
                $this->events['constructive']['tables'][$this->table_name]['indexes'][$this->index_name]['doAddIndex']      = "doAddIndex__{$this->table_name}__{$this->index_name}";
                $this->hooks['constructive']['tables'][$this->table_name]['indexes'][$this->index_name]['afterAddIndex']   = "afterAddIndex__{$this->table_name}__{$this->index_name}";
                //$this->events['tables'][$this->table_name]['indexes'][$this->index_name]['was']  = $this->index['was'];
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield':
                $this->index['fields'][$this->field_name] = $this->field;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-name':
            case 'instructionset-constructive-changeset-change-table-index-add-primary':
            case 'instructionset-constructive-changeset-change-table-index-add-unique':
            case 'instructionset-constructive-changeset-change-table-index-add-was':
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-name':
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-sorting':
                break;

            case 'instructionset-destructive':
                $this->instructionset['destructive'] = $this->destructive_changeset_definition;
            	break;
            case 'instructionset-destructive-changeset':
            	break;
            case 'instructionset-destructive-changeset-name':
                $this->destructive_changeset_definition['name'] = $this->name;
            	break;
            case 'instructionset-destructive-changeset-version':
                $this->destructive_changeset_definition['version'] = $this->version;
            	break;
            case 'instructionset-destructive-changeset-remove':
            	break;
            case 'instructionset-destructive-changeset-change':
            	break;
            case 'instructionset-destructive-changeset-change-table':
            	break;
            case 'instructionset-destructive-changeset-remove-table':
                $this->destructive_changeset_definition['tables']['remove'][$this->table_name] = true;
                $this->hooks['destructive']['tables'][$this->table_name]['self']['beforeRemoveTable']  = "beforeRemoveTable__{$this->table_name}";
                $this->events['destructive']['tables'][$this->table_name]['self']['doRemoveTable']      = "doRemoveTable__{$this->table_name}";
                $this->hooks['destructive']['tables'][$this->table_name]['self']['afterRemoveTable']   = "afterRemoveTable__{$this->table_name}";
            	break;
            case 'instructionset-destructive-changeset-change-table-name':
                $this->destructive_changeset_definition['tables']['change'][$this->table_name] = array();
            	break;
            case 'instructionset-destructive-changeset-change-table-remove':
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field':
                //$this->destructive_changeset_definition['tables']['change'][$this->table_name]['remove'] = array();
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field-name':
                $this->destructive_changeset_definition['tables']['change'][$this->table_name]['remove'][$this->field_name] = true;
                $this->hooks['destructive']['tables'][$this->table_name]['fields'][$this->field_name]['beforeRemoveField'] = "beforeRemoveField__{$this->table_name}__{$this->field_name}";
                $this->events['destructive']['tables'][$this->table_name]['fields'][$this->field_name]['doRemoveField']     = "doRemoveField__{$this->table_name}__{$this->field_name}";
                $this->hooks['destructive']['tables'][$this->table_name]['fields'][$this->field_name]['afterRemoveField']  = "afterRemoveField__{$this->table_name}__{$this->field_name}";
            	break;

            case 'instructionset-destructive-changeset-change-table-index':
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove':
                $this->destructive_changeset_definition['tables']['change'][$this->table_name]['indexes']['remove'][$this->index_name] = true;
                $this->hooks['destructive']['tables'][$this->table_name]['indexes'][$this->index_name]['beforeRemoveIndex'] = "beforeRemoveIndex__{$this->table_name}__{$this->index_name}";
                $this->events['destructive']['tables'][$this->table_name]['indexes'][$this->index_name]['doRemoveIndex']    = "doRemoveIndex__{$this->table_name}__{$this->index_name}";
                $this->hooks['destructive']['tables'][$this->table_name]['indexes'][$this->index_name]['afterRemoveIndex']  = "afterRemoveIndex__{$this->table_name}__{$this->index_name}";
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove-name':
                break;

        }

        unset($this->elements[--$this->count]);
        $this->element = implode('-', $this->elements);
    }

    function &raiseError($msg = null, $xmlecode = 0, $xp = null, $ecode = MDB2_SCHEMA_ERROR_PARSE)
    {
        if (is_null($this->error)) {
            $error = '';
            if (is_resource($msg)) {
                $error.= 'Parser error: '.xml_error_string(xml_get_error_code($msg));
                $xp = $msg;
            } else {
                $error.= 'Parser error: '.$msg;
                if (!is_resource($xp)) {
                    $xp = $this->parser;
                }
            }
            if ($error_string = xml_error_string($xmlecode)) {
                $error.= ' - '.$error_string;
            }
            if (is_resource($xp)) {
                $byte = @xml_get_current_byte_index($xp);
                $line = @xml_get_current_line_number($xp);
                $column = @xml_get_current_column_number($xp);
                $error.= " - Byte: $byte; Line: $line; Col: $column";
            }
            $error.= "\n";
            $this->error =& MDB2_Schema::raiseError($ecode, null, null, $error);
        }
        return $this->error;
    }

    function cdataHandler($xp, $data)
    {
        if ($this->var_mode == true) {
            if (!isset($this->variables[$data])) {
                $this->raiseError('variable "'.$data.'" not found', null, $xp);
                return;
            }
            $data = $this->variables[$data];
        }
//        $i = array_search($this->element, $this->instructionset);
//        if (!$i)
//        {
            $this->test[] = $this->element;
//        }
        switch ($this->element)
        {
            case 'instructionset':
            	break;
            case 'instructionset-name':
                $this->name = $data;
            	break;
            case 'instructionset-version':
                $this->version = $data;
            	break;
            case 'instructionset-comments':
                $this->comments = $data;
            	break;
            case 'instructionset-constructive':
            	break;
            case 'instructionset-constructive-changeset':
            	break;
            case 'instructionset-constructive-changeset-name':
                $this->name = $data;
            	break;
            case 'instructionset-constructive-changeset-version':
                $this->version = $data;
            	break;
            case 'instructionset-constructive-changeset-add':
            	break;
            case 'instructionset-constructive-changeset-add-table':
                $this->table_name = $data;
            	break;
            case 'instructionset-constructive-changeset-change':
            	break;
            case 'instructionset-constructive-changeset-change-table':
            	break;
            case 'instructionset-constructive-changeset-change-table-name':
                $this->table_name = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add':
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field':
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-name':
                $this->field_name = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-type':
                $this->field['type'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-length':
                $this->field['length'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-notnull':
                $this->field['notnull'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-default':
                $this->field['default'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-add-field-was':
                $this->field['was'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-change':
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field':
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-name':
                $this->field_name = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-change-field-type':
                $this->field['type'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-length':
                $this->field['length'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-notnull':
                $this->field['notnull'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-default':
                $this->field['default'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-change-field-was':
                $this->field['was'] = $data;
            	break;
            case 'instructionset-constructive-changeset-change-table-index':
                break;
            case 'instructionset-constructive-changeset-change-table-index-add':
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-name':
                $this->index_name = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-primary':
                $this->index['primary'] = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-unique':
                $this->index['unique'] = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-was':
                $this->index['was'] = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield':
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-name':
                $this->field_name = $data;
                break;
            case 'instructionset-constructive-changeset-change-table-index-add-indexfield-sorting':
                $this->field['sorting'] = $data;
                break;


            case 'instructionset-destructive':
            	break;
            case 'instructionset-destructive-changeset':
            	break;
            case 'instructionset-destructive-changeset-name':
                $this->name = $data;
            	break;
            case 'instructionset-destructive-changeset-version':
                $this->version = $data;
            	break;
            case 'instructionset-destructive-changeset-remove':
            	break;
            case 'instructionset-destructive-changeset-change':
            	break;
            case 'instructionset-destructive-changeset-change-table':
            	break;
            case 'instructionset-destructive-changeset-remove-table':
                $this->table_name = $data;
            	break;
            case 'instructionset-destructive-changeset-change-table-name':
                $this->table_name = $data;
            	break;
            case 'instructionset-destructive-changeset-change-table-remove':
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field':
            	break;
            case 'instructionset-destructive-changeset-change-table-remove-field-name':
                $this->field_name = $data;
            	break;

            case 'instructionset-destructive-changeset-change-table-index':
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove':
//                $this->destructive_changeset_definition['tables']['change'][$this->table_name]['indexes']['remove']['field'] = array();
                break;
            case 'instructionset-destructive-changeset-change-table-index-remove-name':
                $this->index_name = $data;
                break;
        }
    }

    function setData(&$array, $key, $value)
    {
        $array[(count($array)-1)][$key] = $value;
    }
}

?>
