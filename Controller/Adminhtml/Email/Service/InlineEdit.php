<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

// namespace Magento\Cms\Controller\Adminhtml\Block;

use Magento\Backend\App\Action\Context;
use Swissup\Email\Api\ServiceRepositoryInterface as ServiceRepository;
use Magento\Framework\Controller\Result\JsonFactory;

// use Magento\Cms\Api\Data\BlockInterface;

class InlineEdit extends \Magento\Backend\App\Action
{
    /** @var ServiceRepository  */
    protected $serviceRepository;

    /**
     * @var ServiceRepository
     */
    protected $blockRepository;

    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param ServiceRepository $serviceRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        ServiceRepository $serviceRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->blockRepository = $serviceRepository;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $blockId) {
                    /** @var \Magento\Cms\Model\Block $block */
                    $block = $this->blockRepository->getById($blockId);
                    try {
                        $data = $block->getData();
                        $data = array_merge($data, $postItems[$blockId]);
                        $block->setData($data);
                        $this->blockRepository->save($block);
                    } catch (\Exception $e) {
                        $messages[] = $e->getMessage();
                        // $this->getErrorWithBlockId(
                        //     $block,
                        //     __($e->getMessage())
                        // );
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    // /**
    //  * Add block title to error message
    //  *
    //  * @param BlockInterface $block
    //  * @param string $errorText
    //  * @return string
    //  */
    // protected function getErrorWithBlockId(BlockInterface $block, $errorText)
    // {
    //     return '[Block ID: ' . $block->getId() . '] ' . $errorText;
    // }
}
