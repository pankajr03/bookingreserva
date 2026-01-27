<?php

namespace BookneticApp\Providers\FSCode\Clients;

class FSCodeAPIClientContextDto
{
    public string $licenseCode;
    public string $website;
    public string $productVersion;
    public string $phpVersion;
    public string $wordpressVersion;

    public function __construct(
        string $licenseCode,
        string $website,
        string $productVersion,
        string $phpVersion,
        string $wordpressVersion
    ) {
        $this->licenseCode = $licenseCode;
        $this->website = $website;
        $this->productVersion = $productVersion;
        $this->phpVersion = $phpVersion;
        $this->wordpressVersion = $wordpressVersion;
    }
}
