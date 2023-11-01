<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Exoscale extends S3
{
    //https://community.exoscale.com/documentation/platform/exoscale-datacenter-zones/
    /**
     * Exoscale Regions constants
     */
    const CH_GVA_2 = 'ch-gva-2';
    const CH_DK_2 = 'ch-dk-2';
    const DE_FRA_1 = 'de-fra-1';
    const DE_MUC_1 = 'de-muc-1';
    const AT_VIE_1 = 'at-vie-1';
    const AT_VIE_2 = 'at-vie-2';
    const BG_SOF_1 = 'bg-sof-1';

    /**
     * Exoscale Constructor
     *
     * @param string $root
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $apiKey, string $apiSecret, string $bucket, string $region = self::CH_GVA_2, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $apiKey, $apiSecret, $bucket, $region, $acl);
        
        if (isset($this->regionEndpointMap[$region])) {
            $this->headers['host'] = $this->regionEndpointMap[$region];
        } else {
            throw new \InvalidArgumentException('Invalid or unsupported region');
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Exoscale Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Exoscale Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_EXOSCALE;
    }
}
