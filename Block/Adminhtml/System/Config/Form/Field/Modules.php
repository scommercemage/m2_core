<?php

namespace Scommerce\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Scommerce\Core\Model\InstalledModules;


class Modules extends Field
{
    /**
     * @var InstalledModules
     */
    protected $installedModules;

    public function __construct(
        Context $context,
        InstalledModules $installedModules,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->installedModules = $installedModules;

    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $scommerceModules = $this->installedModules->getModuleList();

        $html = "<h1>Modules</h1>";
        $html .= "<p style='font-weight: bold'>Please note: In case you are using the module(s) on your development or staging site, please use the same license key provided in your original order confirmation email.</p>";
        $html .= $this->renderStyles();
        $html .= $this->renderTable($scommerceModules);
        $html .= $this->renderScript();
        return $html;
    }

    private function renderTable($modules)
    {
        $html = '<table id="modules_table" class="modules-table">';
        $html .= '<thead>';
        $html .= '<tr class="modules-header">';
        $html .= '<th class="modules-cell">Name</th>';
        $html .= '<th class="modules-cell">Installed version</th>';
        $html .= '<th class="modules-cell">Available version</th>';
        $html .= '<th class="modules-cell">Latest Extension version</th>';
        $html .= '<th class="modules-cell">Status</th>';
        $html .= '<th class="modules-cell">Verify</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Iterate over each module and create a row
        foreach ($modules as $name => $data) {
            $latestAvailableVersion = isset($data['latest_available_version']) > 0 ? $data['latest_available_version'] : 'No Information Available Now';
            $latestExtensionVersion = isset($data['latest_extension_version']) > 0 ? $data['latest_extension_version'] : 'No Information Available Now';
            $status = isset($data['status']) ? $data['status'] : 'Status';
            $verifyButton = $this->getVerifyButton($name);
            $html .= '<tr class="modules-row" id="' . $name . '">';
            $html .= '<td class="modules-cell">' . htmlspecialchars($data['name']) . '</td>'; // Module name
            $html .= '<td class="modules-cell">' . htmlspecialchars($data['installed_version']) . '</td>'; //installed version
            $html .= '<td class="modules-cell latest-available">' . htmlspecialchars($latestAvailableVersion) . '</td>'; //available version
            $html .= '<td class="modules-cell latest-version">' . htmlspecialchars($latestExtensionVersion) . '</td>'; //latest version
            $html .= '<td class="modules-cell status">' . htmlspecialchars($status) . '</td>'; //status
            $html .= '<td class="modules-cell modules-cell-last">' . $verifyButton . '</td>'; //verify
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    private function renderScript()
    {
        $verifyUrl = $this->getUrl('scverify/license/verify');
        $script = "
            <script>
            require([
                'jquery',
                'prototype',
                'loader'
            ], function($) {
                var verifyUrl = '{$verifyUrl}' + '?isAjax=true';

                function sendVerifyRequest(params) {

                    new Ajax.Request(verifyUrl, {
                       loaderArea: false,
                       asynchronous: true,
                       parameters: params,
                       onSuccess: function(response) {
                           var result = response.responseJSON;
                           if (result.trace) {
                               console.log(result);
                               if (result.message) {
                                   alert(result.message);
                               } else {
                                   alert('Something went wrong during request to API');
                               }
                           } else if (!result.status) {
                                var row = $('#' + params.moduleName);
                                if (result.latest_available_version) {
                                    row.find('.latest-available').text(result.latest_available_version);
                                }
                                if (result.latest_extension_version) {
                                    row.find('.latest-version').text(result.latest_extension_version);
                                }
                                if (result.message) {
                                    row.find('.status').text(result.message);
                                }
                                alert(result.message);
                           }

                           $('#modules_table').trigger('processStop');
                       },
                       onError: function(response) {
                           $('#modules_table').trigger('processStop');
                           console.error(response);
                           alert('Error while sending request');
                       }
                    });
                }

                $('.verify').click(function(event) {
                    $('#modules_table').trigger('processStart');
                    event.preventDefault();
                    websiteId = $(this).data('website-id');
                    moduleName = $(this).data('module-name');
                    sendVerifyRequest({websiteId: websiteId, moduleName: moduleName});
                });
            });
            </script>
        ";

        return $script;
    }

    private function getVerifyButton($moduleName)
    {
        $websiteId = $this->installedModules->getWebsiteId();

        $html = "<button class='verify' data-website-id='{$websiteId}' data-module-name='{$moduleName}'>Verify</button>";

        return $html;
    }

    private function renderStyles()
    {
        $styles = "
            <style>
            .entry-edit-head.admin__collapsible-block {
                display: none;
            }

            #row_scommerce_core_general_dev {
                display: flex;
            }

            #row_scommerce_core_general_modules .label,
            #row_scommerce_core_general_modules .use-default,
            #row_scommerce_core_general_modules  td:last-child {
                display: none;
            }

            #row_scommerce_core_general_modules .value {
            width: 100%;
            }
            #row_scommerce_core_general_modules #modules_table td:last-child {
                display: table-cell;
            }

            /* Table Styles */
            #row_scommerce_core_general_modules #modules_table .modules-table {
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
                margin: 20px 0;
                font-size: 16px;
            }

            #row_scommerce_core_general_modules #modules_table .modules-table thead {
                background-color: #f2f2f2;
            }

            #row_scommerce_core_general_modules #modules_table .modules-header {
                font-weight: bold;
            }

            #row_scommerce_core_general_modules #modules_table .modules-cell {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            #row_scommerce_core_general_modules #modules_table .modules-row:nth-child(even) {
                background-color: #f9f9f9;
            }

            #row_scommerce_core_general_modules #modules_table .modules-row:hover {
                background-color: #f1f1f1;
            }

            #row_scommerce_core_general_modules #modules_table .modules-cell-last {
                font-weight: bold;
                color: red;
            }
            </style>
            ";

        return $styles;
    }
}
