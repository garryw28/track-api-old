<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 *
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @link      https://github.com/WindowsAzure/azure-sdk-for-php
 */

namespace WindowsAzure\ServiceBus\Models;

use WindowsAzure\Common\Internal\Atom\Feed;

/**
 * The result of the list rules request.
 *
 * @category  Microsoft
 *
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @version   Release: 0.5.0_2016-11
 *
 * @link      https://github.com/WindowsAzure/azure-sdk-for-php
 */
class ListRulesResult extends Feed
{
    /**
     * The information of the rule.
     *
     * @var RuleInfo[]
     */
    private $_ruleInfos;

    /**
     * Populates the properties with the response from the list rules request.
     *
     * @param string $response The body of the response of the list rules request
     */
    public function parseXml($response)
    {
        parent::parseXml($response);
        $listRulesResultXml = new \SimpleXMLElement($response);
        $this->_ruleInfos = [];

        foreach ($listRulesResultXml->entry as $entry) {
            $ruleInfo = new RuleInfo();
            $ruleInfo->parseXml($entry->asXML());
            $this->_ruleInfos[] = $ruleInfo;
        }
    }

    /**
     * Gets the information of the rules.
     *
     * @return RuleInfo[]
     */
    public function getRuleInfos()
    {
        return $this->_ruleInfos;
    }

    /**
     * Sets the information of the rule.
     *
     * @param RuleInfo[] $ruleInfos The information of the rule
     */
    public function setRuleInfos(array $ruleInfos)
    {
        $this->_ruleInfos = $ruleInfos;
    }
}
