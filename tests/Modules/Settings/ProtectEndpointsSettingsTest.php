<?php

namespace SimpleJwtLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class ProtectEndpointsSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    public function testInitProperties(){
        $post = [
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => 1,
                'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect' => [
                    'test',
                    '',
                    'test'
                ],
                'whitelist' => [
                    '123',
                    '',
                    '123'
                ]
            ]
        ];
        $settings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);
        $settings->initSettingsFromPost();
        $this->assertTrue($settings->isEnabled());
        $this->assertSame(
            ProtectEndpointSettings::ALL_ENDPOINTS,
            $settings->getAction()
        );
        $this->assertSame(
            [
                'test',
                ''
            ],
            $settings->getProtectedEndpoints()
        );
        $this->assertSame(
            [
                '123',
                ''
            ],
            $settings->getWhitelistedDomains()
        );
    }

    public function testGetDefaultValues(){
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([]);

        $this->assertSame(
            false,
            $settings->isEnabled()
        );
        $this->assertSame(
            0,
            $settings->getAction()
        );
        $this->assertSame(
            [
                ''
            ],
            $settings->getWhitelistedDomains()
        );
        $this->assertSame(
            [
                ''
            ],
            $settings->getProtectedEndpoints()
        );
    }

    public function testValidateWhenNotEnabled(){
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => false
                ]
            ]);
        $this->assertTrue($settings->validateSettings());
    }

    /**
     * @param mixed $endpointLists
     * @throws Exception
     *
     * @dataProvider endpointsProvider
     */
    public function testNoEndpointProvided($endpointLists){
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => $endpointLists
                ]
            ]);
        $this->expectException(Exception::class);
        $this->expectErrorMessage('You need to add at least one endpoint.');
        $settings->validateSettings();
    }

    public function endpointsProvider()
    {
        return [
            'empty_array' => [
                'endpoint_list' => ['']
            ],
            'array_with_empty_values' => [
                'endpoint_list' => [
                    '',
                    '',
                    '',
                ]
            ],
            'array_with_space' => [
                'endpoint_list' => [
                    '    ',
                    '    ',
                ]
            ],
        ];
    }
}