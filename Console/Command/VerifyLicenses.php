<?php

namespace Scommerce\Core\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Scommerce\Core\Model\VerifyLicenses as VerifyLicensesProcess;

class VerifyLicenses extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var VerifyLicensesProcess
     */
    private $verifyLicenses;

    public function __construct(
        State $state,
        VerifyLicensesProcess $verifyLicenses
    ) {
        $this->state = $state;
        $this->verifyLicenses = $verifyLicenses;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('scommerce:licenses:verify')->setDescription('Verify All Licenses');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->emulateAreaCode(
            Area::AREA_CRONTAB,
            [$this, "executeCallBack"],
            [$input, $output]
        );
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function executeCallBack(InputInterface $input, OutputInterface $output)
    {
        $this->verifyLicenses->execute();
        return Cli::RETURN_SUCCESS;
    }
}
