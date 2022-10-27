<?php

/**
 * Force Order Cancel Status Module
 *
 * @author  Peter McWilliams <pmcwilliams@augustash.com>
 * @license MIT
 */

declare(strict_types=1);

namespace Augustash\ForceCancel\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Ui\Component\MassAction\Filter;

class ForceCancel implements HttpPostActionInterface
{
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * Constructor.
     *
     * Initialize class dependencies.
     *
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface $orderCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     */
    public function __construct(
        Filter $filter,
        MessageManagerInterface $messageManager,
        CollectionFactoryInterface $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        ResultFactory $resultFactory
    ) {
        $this->filter = $filter;
        $this->messageManager = $messageManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute action based on request and return result.
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
            $collection = $this->filter->getCollection($this->orderCollectionFactory->create());
            $countCollection = $collection->getSize();
            $countComplete = 0;

            foreach ($collection->getItems() as $order) {
                /** @var \Magento\Sales\Model\Order $order */
                $order
                    ->setState(OrderModel::STATE_CANCELED)
                    ->setStatus(OrderModel::STATE_CANCELED)
                    ->addCommentToStatusHistory('Force canceled by administrator', OrderModel::STATE_CANCELED);
                $this->orderRepository->save($order);
                $countComplete++;
            }

            $countNotComplete = $countCollection - $countComplete;

            if ($countNotComplete > 0 && $countComplete > 0) {
                $this->messageManager->addErrorMessage(\sprintf(
                    '%d order(s) out of %d were not marked as canceled.',
                    $countNotComplete,
                    $countCollection
                ));
            } elseif ($countNotComplete > 0 && $countComplete === 0) {
                $this->messageManager->addErrorMessage('No order(s) were marked as canceled.');
            } else {
                $this->messageManager->addSuccessMessage(\sprintf(
                    '%d order(s) out of %d were marked as canceled.',
                    $countComplete,
                    $countCollection
                ));
            }

            $resultRedirect->setPath('sales/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                new Phrase('Something went wrong while force canceling the order(s).')
            );
            $resultRedirect->setPath('*/*/');
        }

        return $resultRedirect;
    }
}
