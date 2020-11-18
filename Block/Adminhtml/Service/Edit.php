<?php
namespace Swissup\Email\Block\Adminhtml\Service;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize blog post edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Swissup_Email';
        $this->_controller = 'adminhtml_service';

        parent::_construct();

        if ($this->_isAllowedAction('Swissup_Email::service_save')) {
            $this->buttonList->update('save', 'label', __('Save'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );

//            $model = $this->getModel();
//            if ($model->getId()) {
                $onClick = "var params = jQuery('#edit_form').serialize(); "
                    . "if (jQuery('#edit_form').validation('isValid') === false){return false;}"
                    . "uenc = Base64.encode(params); "
                    . 'return setLocation(\'' . $this->getCheckUrl() . '\'.replace(\'ruenc\', uenc))';
                $this->buttonList->add(
                    'check',
                    [
                        'label' => __('Check service'),
                        'onclick' => $onClick,
                        'class' => 'save',
                    ],
                    -90
                );
//            }
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Swissup_Email::service_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    protected function getModel()
    {
        return $this->coreRegistry->registry('email_service');
    }

    /**
     * Retrieve text for header element depending on loaded post
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $model = $this->getModel();
        if ($model->getId()) {
            return __("Edit '%1'", $this->escapeHtml($model->getText()));
        } else {
            return __('New');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }

    public function getCheckUrl()
    {
        return $this->getUrl(
            '*/*/check',
            ['_current' => true, 'back' => 'edit', 'active_tab' => '', 'uenc' => 'ruenc']
        );
    }

    protected function _prepareLayout()
    {
        $this->_formScripts[] = "require(['Swissup_Email/service-settings']);";
        return parent::_prepareLayout();
    }
}
