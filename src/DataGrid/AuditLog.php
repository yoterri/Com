<?php
namespace Com\DataGrid;

use Com\DataGrid\TinyGrid;
use Com\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Com\StringUtils;

class AuditLog extends TinyGrid
{
    
    protected function _buildDatasource()
    {
        $sm = $this->getContainer();        

        $dbAuditLog = $sm->get('Com\Db\AuditLog');
     
        $cols = array(
            // '*',
            'id',
            'created_on',
            'title',
            'url',
            'ip_address',
            'user_id',
            'detail',
        );

        $select = new Select();

        $select->columns($cols);
        $select->from(array('p' => "$dbAuditLog"));

        #
        $isSearching = false;
        $where = new Where();

        #
        $select->where($where);

        # $dbOrder->debugSql($select);

        $this->setSource($select, $sm->get('adapter'));
    }



    protected function _buildColumns()
    {
        $sm = $this->getContainer();
        $eRecord = $sm->get('Com\Entity\Record');

        $columns = array(
            'id' => array(
                'header' => array(
                	'attributes' => array('style' => 'width:100px'),
                    'label' => '#',
                    'sort' => true,
                    'sort_column' => array('id'),

                    'filter' => 1,
                    'filter_type' => '=', # > | < | >= | <=
                    'filter_column' => array('id'),

                    #'attributes' => array('style' => 'width:125px'),
                ),
                'cell' => array(
                    'type' => 'number'
                )
            ),

            'created_on' => array(
                'header' => array(
                	'attributes' => array('style' => 'width:150px'),
                    'label' => 'Date',
                    'sort' => true,
                    #'sort_column' => 'tipo_producto',

                    'filter' => 1,
                    #'filter_column' => array('tipo_producto'),
                ),
                'cell' => array(
                    'type' => 'date',
            		'date_format_to' => 'F HH:mm:ss',
		            'empty_date' => '-'
                )
            ),            
            'title' => array(
                'header' => array(
                    'label' => 'Title',
                    'sort' => 1,

                    'filter' => 1,
                ),
                'cell' => array(
                    
                )
            ),

            'url' => array(
                'header' => array(
                    'label' => 'Url',
                    'sort' => 1,

                    'filter' => 1,
                ),
                'cell' => array(
                    
                )
            ),

            'ip_address' => array(
                'header' => array(
                	'attributes' => array('style' => 'width:150px'),
                    'label' => 'IP',
                    'sort' => 1,

                    'filter' => 1,
                ),
                'cell' => array(
                    
                )
            ),


            'user_id' => array(
                'header' => array(
                	'attributes' => array('style' => 'width:100px'),
                    'label' => 'User',
                    'sort' => 1,

                    'filter' => 1,
                    'filter_type' => '=', # > | < | >= | <=
                ),
                'cell' => array(
                    
                )
            ),


            #'detail' => array(
            #    'header' => array(
            #        'label' => 'Detail',

            #        'filter' => 1,
            #    ),
            #    'cell' => array(
                    
            #    )
            #),
        );

        $this->setColumns($columns);
    }
}


