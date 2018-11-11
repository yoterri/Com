<?php

namespace Com\Html;


/**
 * Component representing a table row.
 *
 * @copyright 2009 Nicolas Connault
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class Row
{

    /**
     * @var string Value to use for the id attribute of the row.
     */
    public $id = null;

    /**
     * @var array Array of html_table_cell objects
     */
    public $cells = array();

    /**
     * @var string Value to use for the style attribute of the table row
     */
    public $style = null;

    /**
     * @var array Attributes of additional HTML attributes for the <tr> element
     */
    public $attributes = array();

    /**
     * Constructor
     * @param array $cells
     */
    public function __construct(array $cells=null) {
        $this->attributes['class'] = '';
        $cells = (array)$cells;
        foreach ($cells as $cell) {
            if ($cell instanceof \Com\Html\Cell) {
                $this->cells[] = $cell;
            } else {
                $this->cells[] = new \Com\Html\Cell($cell);
            }
        }
    }
}