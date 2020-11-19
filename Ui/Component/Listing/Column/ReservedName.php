<?php
namespace Swissup\Email\Ui\Component\Listing\Column;

//use Magento\Framework\View\Element\UiComponent\ContextInterface;
//use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ReservedName extends Column
{
    /**
     * Apply sorting
     *
     * @return void
     */
    protected function applySorting()
    {
        $sorting = $this->getContext()->getRequestParam('sorting');
        $isSortable = $this->getData('config/sortable');
        if ($isSortable !== false
            && !empty($sorting['field'])
            && !empty($sorting['direction'])
            && $sorting['field'] === $this->getName()
        ) {
            $orderColumn = $this->getName();
            $orderColumn = "`{$orderColumn}`";
            $this->getContext()->getDataProvider()->addOrder(
                $orderColumn,
                strtoupper($sorting['direction'])
            );
        }
    }
}
