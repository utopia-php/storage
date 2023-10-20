<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Exoscale extends S3
{
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

    private $regionEndpointMap = [
        self::CH_GVA_2 => 'https://sos-ch-gva-2.exo.io',
        self::CH_DK_2 => 'https://sos-ch-dk-2.exo.io',
        self::DE_FRA_1 => 'https://sos-de-fra-1.exo.io',
        self::DE_MUC_1 => 'https://sos-de-muc-1.exo.io',
        self::AT_VIE_1 => 'https://sos-at-vie-1.exo.io',
        self::AT_VIE_2 => 'https://sos-at-vie-2.exo.io',
        self::BG_SOF_1 => 'https://sos-bg-sof-1.exo.io',
    ];

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