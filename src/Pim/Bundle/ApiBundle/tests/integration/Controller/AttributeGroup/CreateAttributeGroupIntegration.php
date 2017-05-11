<?php

namespace Pim\Bundle\ApiBundle\tests\integration\Controller\AttributeGroup;

use Akeneo\Test\Integration\Configuration;
use Pim\Bundle\ApiBundle\tests\integration\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateAttributeGroupIntegration extends ApiTestCase
{
    public function testHttpHeadersInResponseWhenAnAttributeGroupIsCreated()
    {
        $client = $this->createAuthenticatedClient();
        $data =
<<<JSON
    {
        "code":"technical"
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame('http://localhost/api/rest/v1/attribute-groups/technical', $response->headers->get('location'));
        $this->assertSame('', $response->getContent());
    }

    public function testStandardFormatWhenAnAttributeGroupIsCreatedButIncomplete()
    {
        $client = $this->createAuthenticatedClient();
        $data =
<<<JSON
    {
        "code":"marketing"
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $attributeGroup = $this->get('pim_catalog.repository.attribute_group')->findOneByIdentifier('marketing');

        $attributeGroupStandard = [
            'code'       => 'marketing',
            'sort_order' => 0,
            'attributes' => [],
            'labels'     => [],
        ];
        $normalizer = $this->get('pim_catalog.normalizer.standard.attribute_group');

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($attributeGroupStandard, $normalizer->normalize($attributeGroup));
    }

    public function testCompleteAttributeGroupCreation()
    {
        $client = $this->createAuthenticatedClient();
        $data =
<<<JSON
    {
        "code":"manufacturing",
        "sort_order": 6,
        "attributes": [
            "sku",
            "a_date",
            "a_file"
        ],
        "labels": {
            "en_US": "Manufacturing",
            "fr_FR": "Fabrication"
        }
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $attributeGroup = $this->get('pim_catalog.repository.attribute_group')->findOneByIdentifier('manufacturing');

        $attributeGroupStandard = [
            'code'       => 'manufacturing',
            'sort_order' => 6,
            'attributes' => ['sku', 'a_date', 'a_file'],
            'labels'     => [
                'en_US' => 'Manufacturing',
                'fr_FR' => 'Fabrication',
            ],
        ];
        $normalizer = $this->get('pim_catalog.normalizer.standard.attribute_group');

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($attributeGroupStandard, $normalizer->normalize($attributeGroup));
    }

    public function testAttributeGroupCreationWithEmptyLabels()
    {
        $client = $this->createAuthenticatedClient();
        $data =
<<<JSON
    {
        "code":"empty_label_attribute_group",
        "sort_order": 7,
        "labels": {
            "en_US": null,
            "fr_FR": ""
        }
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $attributeGroup = $this->get('pim_catalog.repository.attribute_group')->findOneByIdentifier('empty_label_attribute_group');

        $attributeGroupStandard = [
            'code'       => 'empty_label_attribute_group',
            'sort_order' => 7,
            'attributes' => [],
            'labels'     => [],
        ];
        $normalizer = $this->get('pim_catalog.normalizer.standard.attribute_group');

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($attributeGroupStandard, $normalizer->normalize($attributeGroup));
    }

    public function testResponseWhenContentIsEmpty()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], '');

        $expectedContent =
<<<JSON
{
	"code": 400,
	"message": "Invalid json message received"
}
JSON;

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($expectedContent, $response->getContent());
    }

    public function testResponseWhenContentIsNotValid()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], '{');

        $expectedContent =
<<<JSON
{
	"code": 400,
	"message": "Invalid json message received"
}
JSON;

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($expectedContent, $response->getContent());
    }

    public function testResponseWhenAttributeGroupCodeAlreadyExists()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "code":"technical"
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => [
                [
                    'property' => 'code',
                    'message'  => 'This value is already used.',
                ]
            ],
        ];

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenAttributeGroupCodeIsNotScalar()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "code":[]
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "code" expects a scalar as data, "array" given. Check the standard format documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#post_attribute_groups',
                ]
            ]
        ];

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenAPropertyIsNotExpected()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "code":"new_attribute_group",
        "extra_property": ""
    }
JSON;
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "extra_property" does not exist. Check the standard format documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#post_attribute_groups',
                ],
            ],
        ];

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenArrayExpectedValueHasAnInvalidStructure()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "labels":{
            "en_US": []
        }
    }
JSON;
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "labels" expects an array with valid data, one of the "labels" values is not a scalar. Check the standard format documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#post_attribute_groups',
                ]
            ]
        ];

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenLabelsIsNull()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "labels":null
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "labels" expects an array as data, "NULL" given. Check the standard format documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#post_attribute_groups',
                ],
            ],
        ];

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenAttributesIsNull()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "attributes":null
    }
JSON;

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "attributes" expects an array as data, "NULL" given. Check the standard format documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#post_attribute_groups',
                ],
            ],
        ];

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenLocaleCodeInLabelsIsEmpty()
    {
        $client = $this->createAuthenticatedClient();

        $data =
<<<JSON
    {
        "code":"unknown_locale",
        "labels": {
            "":"label"
        }
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => [
                [
                    'property' => 'labels',
                    'message'  => 'The locale "" does not exist.',
                ],
            ],
        ];

        $client->request('POST', 'api/rest/v1/attribute-groups', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testResponseWhenLocaleCodeDoesNotExist()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "code":"unknown_locale",
        "type":"pim_catalog_text",
        "group":"attributeGroupA",
        "labels": {
            "foo": "label"
        }
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => [
                [
                    'property' => 'labels',
                    'message'  => 'The locale "foo" does not exist.',
                ],
            ],
        ];

        $client->request('POST', 'api/rest/v1/attributes', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration(
            [Configuration::getTechnicalCatalogPath()],
            false
        );
    }
}
