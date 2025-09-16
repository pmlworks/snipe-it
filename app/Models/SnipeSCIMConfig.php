<?php

namespace App\Models;

use ArieTimmerman\Laravel\SCIMServer\Helper;
use ArieTimmerman\Laravel\SCIMServer\SCIM\Schema;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Attribute;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Collection;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Complex;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Constant;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Eloquent;
use ArieTimmerman\Laravel\SCIMServer\Attribute\JSONCollection;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Meta;
use ArieTimmerman\Laravel\SCIMServer\Attribute\MutableCollection;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Schema as AttributeSchema;
use Illuminate\Database\Eloquent\Model;

function a($name = null): Attribute
{
    return new Attribute($name);
}

function complex($name = null): Complex
{
    return new Complex($name);
}

function eloquent($name, $attribute = null): Attribute
{
    return new Eloquent($name, $attribute);
}

class MappedTable extends Attribute
{
    public function __construct(
        private string $scim_attribute_name,
        private string $relationship_name,
        private string $relationship_class,
        private string $relationship_field = 'name')
    {
        parent::__construct($this->scim_attribute_name);
    }

    protected function doRead(&$object, $attributes = [])
    {
        return $object->{$this->relationship_name}?->{$this->relationship_field};
    }

    public function add($value, Model &$object)
    {
        \Log::error("Structure of 'value' is going to be weird - " . print_r($value, true));
        $object->{$this->relationship_name} = $value ? $relationship_class::firstOrCreate([$this->relationship_field => $value]) : null;
    }

    public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
    {
        $object->{$this->relationship_name} = $value ? $relationship_class::firstOrCreate([$this->relationship_field => $value]) : null;
    }

}


class SnipeSCIMConfig
{
    public function __construct()
    {
    }

    public function getConfigForResource($name)
    {
        $result = $this->getConfig();
        return @$result[$name];
    }

    public function getGroupClass()
    {
        return Group::class;
    }

    const ENTERPRISE = 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User';
    const GROKABILITY = 'urn:ietf:params:scim:schemas:extension:grokability:2.0:User';

    public function getUserConfig()
    {
        return [

            // Set to 'null' to make use of auth.providers.users.model (App\User::class)
            'class' => Helper::getAuthUserClass(),
            'singular' => 'User',

            //eager loading
            'withRelations' => [],
            'description' => 'User Account',

            'map' => complex()->withSubAttributes(
                new class ('schemas', [
                    "urn:ietf:params:scim:schemas:core:2.0:User",
                    self::ENTERPRISE,
                    self::GROKABILITY
                ]) extends Constant {
                    public function replace($value, &$object, $path = null)
                    {
                        // do nothing
                        $this->dirty = true;
                    }
                },
                (new class ('id', null) extends Constant { // TODO - this 'id' is in the same namespace for objects OR groups?
                    protected function doRead(&$object, $attributes = [])
                    {
                        return (string)$object->id;
                    }

                    public function remove($value, &$object, $path = null)
                    {
                        // do nothing
                    }
                }
                ),
                new Meta('Users'),
                (new AttributeSchema(Schema::SCHEMA_USER, true))->withSubAttributes(
                    eloquent('userName', 'username')->ensure('required'),
                    (new class ('active', 'activated') extends Eloquent {
                        protected function doRead(&$object, $attributes = [])
                        {
                            return (bool)$object->activated; // need this extension to force boolean-ness
                        }
                    }),
                    complex('name')->withSubAttributes(
                        eloquent('givenName', 'first_name')->ensure('required'),
                        eloquent('familyName', 'last_name'),
                    )->ensure('required'),
                    eloquent('displayName', 'display_name'), //yes, this is *not* under 'name' - that's the spec
                    //eloquent('password')->ensure('nullable')->setReturned('never'),
                    eloquent('externalId', 'scim_externalid'),

                    // Email chonk
                    (new class ('emails') extends Complex {
                        protected function doRead(&$object, $attributes = [])
                        {
                            return collect([$object->email])->map(function ($email) {
                                return [
                                    'value' => $email,
                                    'type' => 'work', //TODO - is this how we always have done it?
                                    'primary' => true
                                ];
                            })->toArray();
                        }

                        public function add($value, Model &$object)
                        {
                            $object->email = $value[0]['value'];
                        }

                        public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
                        {
                            $object->email = $value[0]['value'];
                        }
                    })->withSubAttributes(
                        eloquent('value', 'email')->ensure('email'),
                        new Constant('type', 'work'),
                        new Constant('primary', true)->ensure('boolean')
                    )->ensure('array')
                        ->setMultiValued(true),

                    // phone chonk
                    (new class ('phoneNumbers') extends Complex {
                        protected function doRead(&$object, $attributes = [])
                        {
                            $phones = [];
                            if ($object->phone) {
                                $phones[] = [
                                    'value' => $object->phone,
                                    'type' => 'work'
                                ];
                            }
                            if ($object->mobile) {
                                $phones[] = [
                                    'value' => $object->mobile,
                                    'type' => 'mobile'
                                ];
                            }
                            return $phones;
                        }

                        public function add($value, Model &$object)
                        {
                            throw new \Exception("Dunno about fones");
                            $object->email = $value[0]['value'];
                        }

                        public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)

                        {
                            throw new \Exception ("still dunno afbout fones");
                            $object->email = $value[0]['value'];
                        }
                    })->withSubAttributes(
                        new Constant('value', 'email')->ensure('string'),
                        new Constant('type', 'other'),
                        new Constant('primary', true)->ensure('boolean'),
                    )->ensure('array')
                        ->setMultiValued(true),

                    // addresses chonk
                    (new class ('addresses') extends Complex {
                        protected function doRead(&$object, $attributes = [])
                        {
                            $addressmap = [
                                'streetAddress' => 'address',
                                'locality' => 'city',
                                'region' => 'state',
                                'postalCode' => 'zip',
                                'country' => 'country'
                            ];
                            $address = [];
                            foreach ($addressmap as $scim_field => $db_field) {
                                if ($object->{$db_field}) {
                                    $address[$scim_field] = $object->{$db_field};
                                }
                            }
                            if (count($address) > 0) {
                                $address['type'] = 'work';
                                $address['primary'] = true;
                            }
                            return $address;
                        }

                        /*** It's possible that the Eloquent mappings in the sub-attributes will handle this?
                        public function add($value, Model &$object)
                        {
                            throw new \Exception("Dunno about addresses to add");
                            $object->email = $value[0]['value'];
                        }

                        public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)

                        {
                            throw new \Exception ("still dunno afbout addresses to whatever");
                            $object->email = $value[0]['value'];
                        }
                         * *********/
                    })->withSubAttributes(
                        eloquent('streetAddress', 'address'),
                        eloquent('locality', 'city'),
                        eloquent('region', 'state'),
                        eloquent('postalCode', 'zip'),
                        eloquent('country', 'country'),
                        new Constant('type', 'other'),
                        new Constant('primary', true)->ensure('boolean')
                    )->ensure('array')
                        ->setMultiValued(true),

                    eloquent('title', 'jobtitle'),
                    eloquent('preferredLanguage', 'locale'),
                    (new Collection('groups'))->withSubAttributes(
                        eloquent('value', 'id'),
                        (new class ('$ref') extends Eloquent {
                            protected function doRead(&$object, $attributes = [])
                            {
                                return route(
                                    'scim.resource',
                                    [
                                        'resourceType' => 'Group',
                                        'resourceObject' => $object->id ?? "not-saved"
                                    ]
                                );
                            }
                        }),
                        eloquent('display', 'name')
                    ),
                    (new JSONCollection('roles'))->withSubAttributes( // TODO - what is this?
                        eloquent('value')->ensure('required', 'min:3', 'alpha_dash:ascii'),
                        eloquent('display')->ensure('nullable', 'min:3', 'alpha_dash:ascii'),
                        eloquent('type')->ensure('nullable', 'min:3', 'alpha_dash:ascii'),
                        eloquent('primary')->ensure('boolean')->default(false)
                    )->ensure('nullable', 'array', 'max:20')
                ),
                (new AttributeSchema(self::ENTERPRISE, false))->withSubAttributes(
                    eloquent('employeeNumber', 'employee_num')->ensure('nullable'),
                    new MappedTable('department', 'department', Department::class, 'name'),
                    //eloquent('manager', 'manager_id'), // FIXME - this is going to be more complicated and map to 'value'
                    (new class('manager') extends Complex {
                        protected function doRead(&$object, $attributes = [])
                        {
                            if (!$object->manager) {
                                return null;
                            }
                            return [
                                'value' => $object->manager->id, //TODO - ID's aren't unique like they're supposed to be :/
                                '$ref' => route('scim.resource', ['resourceType' => 'User', 'resourceObject' => $object->manager->id]),
                                'displayName' => $object->manager->display_name,
                            ];
                        }
                    }) // ->withSubAttributes() ... -> ensure() ?
                ),
                (new AttributeSchema(self::GROKABILITY, false))->withSubAttributes(
                    new MappedTable('location', 'location', Location::class, 'name'),
                    new MappedTable('company', 'company', Company::class, 'name'),
                )
            ),
        ];
    }

    public function getGroupConfig()
    {
        return [

            'class' => $this->getGroupClass(),
            'singular' => 'Group',

            //eager loading
            'withRelations' => [],
            'description' => 'Group',

            'map' => complex()->withSubAttributes(
                new class ('schemas', [
                    "urn:ietf:params:scim:schemas:core:2.0:Group",
                ]) extends Constant {
                    public function replace($value, &$object, $path = null)
                    {
                        // do nothing
                        $this->dirty = true;
                    }
                },
                (new class ('id', null) extends Constant {
                    protected function doRead(&$object, $attributes = [])
                    {
                        return (string)$object->id;
                    }

                    public function remove($value, &$object, $path = null)
                    {
                        // do nothing
                    }
                }
                ),
                new Meta('Groups'),
                (new AttributeSchema(Schema::SCHEMA_GROUP, true))->withSubAttributes(
                    eloquent('displayName')->ensure('required', 'min:3', function ($attribute, $value, $fail) {
                        // check if group does not exist or if it exists, it is the same group
                        $group = $this->getGroupClass()::where('displayName', $value)->first();
                        if ($group && (request()->route('resourceObject') == null || $group->id != request()->route('resourceObject')->id)) {
                            $fail('The name has already been taken.');
                        }
                    }),
                    (new MutableCollection('members'))->withSubAttributes(
                        eloquent('value', 'id')->ensure('required'),
                        (new class ('$ref') extends Eloquent {
                            protected function doRead(&$object, $attributes = [])
                            {
                                return route(
                                    'scim.resource',
                                    [
                                        'resourceType' => 'Users',
                                        'resourceObject' => $object->id ?? "not-saved"
                                    ]
                                );
                            }
                        }),
                        eloquent('display', 'name')
                    )->ensure('nullable', 'array')
                )
            ),
        ];
    }

    public function getConfig()
    {
        return [
            'Users' => $this->getUserConfig(),
            'Groups' => $this->getGroupConfig(),
        ];
    }
}
