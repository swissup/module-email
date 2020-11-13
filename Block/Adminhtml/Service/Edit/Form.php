<?php
namespace Swissup\Email\Block\Adminhtml\Service\Edit;

/**
 * Adminhtml service edit form
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    private $serializer;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('Service_form');
        $this->setTitle(__('Service Information'));
    }

   /**
    *
    * @return \Swissup\Email\Model\Service
    */
    protected function _getModel()
    {
        return $this->_coreRegistry->registry('email_service');
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Swissup\Email\Model\Service $model */
        $model = $this->_getModel();

        $isElementDisabled = !$this->_isAllowedAction('Swissup_Email::service_save');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('service_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Service Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField('id', 'hidden', ['name' => 'id']);

        $fieldset->addField(
            'name',
            'text',
            [
                'name'     => 'name',
                'label'    => __('Name'),
                'title'    => __('Name'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $isNew = $model->getId() == null;
        if ($isNew) {
            $smtpSettings = [];
            foreach ($model->getPreDefinedSmtpProviderSettings() as $settings) {
                $smtpSettings[] = [
                    'label' => $settings['name'],
                    'value' => \Swissup\Email\Model\Service::TYPE_SMTP,
                    'title' => $this->serializer->serialize(
                        $this->serializer->serialize($settings)
                    )
                ];
            }
        }
        $values = [];
        foreach ($model->getTypes() as $value => $label) {
            if ($isNew && \Swissup\Email\Model\Service::TYPE_SMTP === $value) {
                $values[$value] = ['label' => $label, 'value' => $smtpSettings];
            } else {
                $values[$value] = ['label' => $label, 'value' => $value];
            }
        }
        $fieldset->addField(
            'type',
            'select',
            [
                'label'    => __('Type'),
                'title'    => __('Type'),
                'name'     => 'type',
                'required' => true,
                // 'options'  => $model->getTypes(),
                'values' => $values,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'host',
            'text',
            [
                'name'     => 'host',
                'label'    => __('Host'),
                'title'    => __('Host'),
                // 'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'port',
            'text',
            [
                'name'     => 'port',
                'label'    => __('Port'),
                'title'    => __('Port'),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'auth',
            'select',
            [
                'label'    => __('Auth Type'),
                'title'    => __('Auth Type'),
                'name'     => 'auth',
                'options'  => $model->getAuthTypes(),
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'user',
            'text',
            [
                'name'     => 'user',
                'label'    => __('User (key)'),
                'title'    => __('User (key)'),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name'     => 'password',
                'label'    => __('Password (secure key)'),
                'title'    => __('Password (secure key)'),
                // 'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'secure',
            'select',
            [
                'label'    => __('Secure'),
                'title'    => __('Secure'),
                'name'     => 'secure',
                'options'  => $model->getSecures(),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'email',
            'text',
            [
                'name'     => 'email',
                'label'    => __('Email (from)'),
                'title'    => __('Email'),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'label'    => __('Status'),
                'title'    => __('Status'),
                'name'     => 'status',
                'options'  => $model->getStatuses(),
                'disabled' => $isElementDisabled,
            ]
        );

        $this->setForm($form);
        $form->setValues($model->getData());
        $form->setUseContainer(true);

        return parent::_prepareForm();
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
}
