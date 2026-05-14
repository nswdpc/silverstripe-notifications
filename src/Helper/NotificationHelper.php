<?php

namespace Symbiote\Notifications\Helper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;

/**
 * A helper for retrieving keywords etc
 *
 * @author  marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class NotificationHelper
{
    /**
     * @var array
     */
    protected $availableKeywords = [];

    public function __construct(private readonly \SilverStripe\ORM\DataObject $owner)
    {
    }

    /**
     * Return a list of all available keywords in the format
     * eg. array(
     *    'keyword' => 'A description'
     * )
     */
    public function getAvailableKeywords(): array
    {
        if ($this->availableKeywords === []) {
            $objectFields = DataObject::getSchema()->databaseFields($this->owner::class);

            $objectFields['Created'] = 'Created';
            $objectFields['LastEdited'] = 'LastEdited';
            $objectFields['Link'] = 'Link';

            $this->availableKeywords = [];

            foreach ($objectFields as $key => $value) {
                $this->availableKeywords[$key] = $key;
            }
        }

        return $this->availableKeywords;
    }

    /**
     * Gets a replacement for a keyword
     */
    public function getKeyword(string $keyword): string
    {
        $k = $this->getAvailableKeywords();

        if ($keyword === 'Link' && $this->owner->hasMethod('Link')) {
            $link = Director::makeRelative($this->owner->Link());

            return Controller::join_links(Director::absoluteBaseURL(), $link);
        }

        if (isset($k[$keyword]) && $this->owner->hasField($keyword)) {
            return $this->owner->getField($keyword);
        }

        return '';
    }
}
