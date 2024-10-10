<?php

namespace Scommerce\Core\Cron;

use Scommerce\Core\Model\VerifyLicenses as VerifyLicensesProcess;
class VerifyLicenses
{
    /**
     * @var VerifyLicensesProcess
     */
    protected $verifyLicenses;

    public function __construct(VerifyLicensesProcess $verifyLicenses)
    {
        $this->verifyLicenses = $verifyLicenses;
    }

    public function execute()
    {
        $this->verifyLicenses->execute(true);
        return true;
    }
}
