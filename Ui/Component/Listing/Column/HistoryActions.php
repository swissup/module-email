<?php
namespace Swissup\Email\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class HistoryActions extends Column
{
    /** Url path */
    const ITEM_URL_PATH_VIEW = 'adminhtml/email_history/view';

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $idKey = 'entity_id';
                if (isset($item[$idKey])) {
                    $href = $this->getContext()->getUrl(
                        self::ITEM_URL_PATH_VIEW,
                        [$idKey => $item[$idKey]]
                    );
                    $onclick = 'var popWin=window.open(\'' . $href . '\',\'_blank\',\'width=1000,height=800,resizable=1, scrollbars=1\');popwin.focus();return false;';
                    $title = __('View');

                    $item[$name] = '<a href ="#" onclick="' . $onclick . '">' . __('View') . '</a>';
                }
            }
        }

        return $dataSource;
    }
}
