<?php

declare(strict_types=1);

namespace Symbiote\Notifications\Model;

/**
 * NotifiedOn
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
interface NotifiedOn
{
    /**
     * Return a list of available keywords in the format
     * array('keyword' => 'A description') to help users format notification fields
     */
    public function getAvailableKeywords(): array;

    /**
     * Gets an associative array of data that can be accessed in
     * notification fields and templates
     */
    public function getNotificationTemplateData(): array;

    /**
     * Gets the list of recipients for a given notification event, based on this object's
     * state.
     * @return array each value in an array is an object
     * In some cases the value must be a Member (InternalNotificationSender)
     * In other cases (EmailNotificationSender) it can be an object with an Email property getEmailAddress method
     *
     */
    public function getRecipients(string $event): array;
}
