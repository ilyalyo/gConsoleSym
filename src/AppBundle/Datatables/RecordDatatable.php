<?php

namespace AppBundle\Datatables;

use Sg\DatatablesBundle\Datatable\View\AbstractDatatableView;
use Sg\DatatablesBundle\Datatable\View\Style;

/**
 * Class RecordDatatable
 *
 * @package AppBundle\Datatables
 */
class RecordDatatable extends AbstractDatatableView
{
    /**
     * {@inheritdoc}
     */
    public function buildDatatable(array $options = array())
    {
        $this->ajax->set(array(
            'url' => $this->router->generate('record_results',  array('id' => $options['website_id'])),
            'type' => 'GET',
            'pipeline' => 5
        ));

        $this->features->set(array(
            'scroll_x' => false,
            'extensions' => array(
                'buttons' =>
                    array(
                        'colvis',
                    ),
                'responsive' => true,
            ),
            'highlight' => true,
            'highlight_color' => 'yellow'
        ));

        $this->options->set(array(
            'length_menu' => array(10, 25, 50, 100),
            'class' => Style::BOOTSTRAP_3_STYLE,
            'individual_filtering' => true,
            'dom' => 'lrtip',
        ));

        $this->columnBuilder
           ->add('dateString', 'datetime', array(
                'title' => 'Date',
                'filter' => array('daterange', array())
           ))
            ->add('query', 'column', array(
                'title' => 'Query',
            ))
            ->add('page', 'column', array(
                'title' => 'Page',
            ))
            ->add('country', 'column', array(
                'title' => 'Country',
            ))
            ->add('device', 'column', array(
                'title' => 'Device',
            ))
            ->add('clicks', 'column', array(
                'title' => 'Clicks',
            ))
            ->add('impressions', 'column', array(
                'title' => 'Impressions',
            ))
            ->add('ctr', 'column', array(
                'title' => 'Ctr',
            ))
            ->add('position', 'column', array(
                'title' => 'Position',
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return 'AppBundle\Entity\Record';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'record_datatable';
    }
}
