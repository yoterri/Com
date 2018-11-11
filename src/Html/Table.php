<?php

namespace Com\Html;


/**
 * Holds all the information required to render a <table> by {@link core_renderer::table()}
 *
 * Example of usage:
 * $t = new html_table();
 * ... // set various properties of the object $t as described below
 * echo html_writer::table($t);
 *
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class Table {

    /**
     * @var string Value to use for the id attribute of the table
     */
    public $id = null;

    /**
     * @var array Attributes of HTML attributes for the <table> element
     */
    public $attributes = array();

    /**
     * @var array An array of headings. The n-th array item is used as a heading of the n-th column.
     * For more control over the rendering of the headers, an array of html_table_cell objects
     * can be passed instead of an array of strings.
     *
     * Example of usage:
     * $t->head = array('Student', 'Grade');
     */
    public $head;

    /**
     * @var array An array that can be used to make a heading span multiple columns.
     * In this example, {@link html_table:$data} is supposed to have three columns. For the first two columns,
     * the same heading is used. Therefore, {@link html_table::$head} should consist of two items.
     *
     * Example of usage:
     * $t->headspan = array(2,1);
     */
    public $headspan;

    /**
     * @var array An array of column alignments.
     * The value is used as CSS 'text-align' property. Therefore, possible
     * values are 'left', 'right', 'center' and 'justify'. Specify 'right' or 'left' from the perspective
     * of a left-to-right (LTR) language. For RTL, the values are flipped automatically.
     *
     * Examples of usage:
     * $t->align = array(null, 'right');
     * or
     * $t->align[1] = 'right';
     */
    public $align;

    /**
     * @var array The value is used as CSS 'size' property.
     *
     * Examples of usage:
     * $t->size = array('50%', '50%');
     * or
     * $t->size[1] = '120px';
     */
    public $size;

    /**
     * @var array An array of wrapping information.
     * The only possible value is 'nowrap' that sets the
     * CSS property 'white-space' to the value 'nowrap' in the given column.
     *
     * Example of usage:
     * $t->wrap = array(null, 'nowrap');
     */
    public $wrap;

    /**
     * @var array Array of arrays or html_table_row objects containing the data. Alternatively, if you have
     * $head specified, the string 'hr' (for horizontal ruler) can be used
     * instead of an array of cells data resulting in a divider rendered.
     *
     * Example of usage with array of arrays:
     * $row1 = array('Harry Potter', '76 %');
     * $row2 = array('Hermione Granger', '100 %');
     * $t->data = array($row1, $row2);
     *
     * Example with array of html_table_row objects: (used for more fine-grained control)
     * $cell1 = new html_table_cell();
     * $cell1->text = 'Harry Potter';
     * $cell1->colspan = 2;
     * $row1 = new html_table_row();
     * $row1->cells[] = $cell1;
     * $cell2 = new html_table_cell();
     * $cell2->text = 'Hermione Granger';
     * $cell3 = new html_table_cell();
     * $cell3->text = '100 %';
     * $row2 = new html_table_row();
     * $row2->cells = array($cell2, $cell3);
     * $t->data = array($row1, $row2);
     */
    public $data;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var string Width of the table, percentage of the page preferred.
     */
    public $width = null;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var string Alignment for the whole table. Can be 'right', 'left' or 'center' (default).
     */
    public $tablealign = null;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var int Padding on each cell, in pixels
     */
    public $cellpadding = null;

    /**
     * @var int Spacing between cells, in pixels
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     */
    public $cellspacing = null;

    /**
     * @var array Array of classes to add to particular rows, space-separated string.
     * Classes 'r0' or 'r1' are added automatically for every odd or even row,
     * respectively. Class 'lastrow' is added automatically for the last row
     * in the table.
     *
     * Example of usage:
     * $t->rowclasses[9] = 'tenth'
     */
    public $rowclasses;

    /**
     * @var array An array of classes to add to every cell in a particular column,
     * space-separated string. Class 'cell' is added automatically by the renderer.
     * Classes 'c0' or 'c1' are added automatically for every odd or even column,
     * respectively. Class 'lastcol' is added automatically for all last cells
     * in a row.
     *
     * Example of usage:
     * $t->colclasses = array(null, 'grade');
     */
    public $colclasses;

    /**
     * @var string Description of the contents for screen readers.
     */
    public $summary;

    /**
     * Constructor
     */
    public function __construct() {
        $this->attributes['class'] = '';
    }
}