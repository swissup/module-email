<?php
namespace Swissup\Email\Api\Data;

/* Swissup/Email/Api/Data/HistoryInterface.php */
interface HistoryInterface
{
    const ENTITY_ID = 'entity_id';
    const FROM = 'from';
    const TO = 'to';
    const SUBJECT = 'subject';
    const BODY = 'body';
    const SERVICE_ID = 'service_id';
    const CREATED_AT = 'created_at';

    /**
     * Get entity_id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom();

    /**
     * Get to
     *
     * @return string
     */
    public function getTo();

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject();

    /**
     * Get body
     *
     * @return string
     */
    public function getBody();

    /**
     * Get service_id
     *
     * @return int
     */
    public function getServiceId();

    /**
     * Get created_at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set entity_id
     *
     * @param int $entityId
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setEntityId($entityId);

    /**
     * Set from
     *
     * @param string $from
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setFrom($from);

    /**
     * Set to
     *
     * @param string $to
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setTo($to);

    /**
     * Set subject
     *
     * @param string $subject
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setSubject($subject);

    /**
     * Set body
     *
     * @param string $body
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setBody($body);

    /**
     * Set service_id
     *
     * @param int $serviceId
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setServiceId($serviceId);

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setCreatedAt($createdAt);
}
