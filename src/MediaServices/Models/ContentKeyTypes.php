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
 * @copyright Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */

namespace WindowsAzure\MediaServices\Models;

/**
 * Represents ContentKey type enum used in media services.
 *
 * @category  Microsoft
 *
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @version   Release: 0.5.0_2016-11
 *
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */
class ContentKeyTypes
{
    /**
     * The content key type "CommonEncryption".
     *
     * @var int
     */
    const COMMON_ENCRYPTION = 0;

    /**
     * The content key type "StorageEncryption".
     *
     * @var int
     */
    const STORAGE_ENCRYPTION = 1;

    /**
     * The content key type "ConfigurationEncryption".
     *
     * @var int
     */
    const CONFIGURATION_ENCRYPTION = 2;

    /**
     * The content key type "ConfigurationEncryption".
     *
     * @var int
     */
    const ENVELOPE_ENCRYPTION = 4;

    /**
     * Specifies a content key for common encryption with CBCS.
     *
     * @var int
     */
    const COMMON_ENCRYPTION_CBCS = 6;

    /**
     * Application Secret key for FairPlay.
     *
     * @var int
     */
    const FAIRPLAY_ASK = 7;

    /**
     * Password for FairPlay application certificate.
     *
     * @var int
     */
    const FAIRPLAY_PFXPASSWORD = 8;
}
