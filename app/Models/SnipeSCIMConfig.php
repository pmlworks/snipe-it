<?php

namespace App\Models;

use ArieTimmerman\Laravel\SCIMServer\Helper;
use ArieTimmerman\Laravel\SCIMServer\Parser\Path;
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
        private string $relationship_id_field,
        private string $relationship_field)
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
        $object->{$this->relationship_id_field} = $value ? $this->relationship_class::firstOrCreate([$this->relationship_field => $value])->id : null;
    }

    public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
    {
        $object->{$this->relationship_id_field} = $value ? $this->relationship_class::firstOrCreate([$this->relationship_field => $value])->id : null;
    }

    public function patch($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
    {
        \Log::error("implementing custom patch for value: '$value' of attribute " . $this->scim_attribute_name);
        $object->{$this->relationship_id_field} = $value ? $this->relationship_class::firstOrCreate([$this->relationship_field => $value])->id : null;
    }

}

class UpdatableComplex extends Complex
{

    public function doWrite($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
    {
        throw new \Exception("doWrite is not implemented yet for Operation: $operation on attribute " . $this->getFullKey());
    }

    public function add($value, Model &$object)
    {
        $this->doWrite("add", $value, $object);
    }

    public function replace($value, Model &$object, Path $path = null, $removeIfNotSet = false)
    {
        $this->doWrite("replace", $value, $object, $path, $removeIfNotSet);
    }

    public function patch($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
    {
        //FIXME - what to do with $operation?!?!!?
        // Also - we don't really have a good repeatable way to do this :/
        // so we're probably going to end up just overriding this anyways :(
        $this->doWrite("patch", $value, $object, $path, $removeIfNotSet);
    }

    public function remove($value, Model &$object, Path $path = null)
    {
        $this->doWrite("remove", null, $object, $path);
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
            'class' => ScimUser::class,
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
                    ), //     ->ensure('required'),  It *is* a bit weird, but I would've thought 'name' is required since 'givenName' is required? But apparently not?
                    eloquent('displayName', 'display_name'), //yes, this is *not* under 'name' - that's the spec
                    //eloquent('password')->ensure('nullable')->setReturned('never'),
                    eloquent('externalId', 'scim_externalid'),

                    // Email chonk
                    (new class ('emails') extends UpdatableComplex {
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

                        public function doWrite($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
                        {
                            if ($value) {
                                $object->email = $value[0]['value'];
                            } else {
                                $object->email = null;
                            }
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

                        public function doWrite($operation, $value, Model &$object, Path $path = null)
                        {
                            \Log::error("Phones 'value' is: " . print_r($value, true));
                            foreach ($value as $phone) {
                                switch ($phone['type']) {
                                    case 'work':
                                        $object->phone = $phone['value'];
                                        break;

                                    case 'mobile':
                                        $object->mobile = $phone['value'];
                                        break;

                                    default:
                                        throw new \Exception("Unknown phone type '{$phone['type']}'");
                                }
                            }
                        }

                        public function patch($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
                        {
                            if ($path->getValuePathFilter() != null) {
                                \Log::error("value object IS: " . print_r($value, true));
                                if ((string)$path == 'phoneNumbers[type eq "mobile"].value') {
                                    \Log::error("YAY!!!!! We are patching an mobile fone! We can do this!"); //FIXME
                                    $object->mobile = $value; //I don't know why the value is the raw value, but it is?
                                    return;
                                }
                                if ((string)$path == 'phoneNumbers[type eq "work"].value') {
                                    \Log::error("Patching work number!");
                                    $object->phone = $value; //similar, don't know why, but it is
                                    return;
                                }
                                \Log::error("Uh-oh, maybe doing something weirder - path is: $path");
                            }
                            parent::patch($operation, $value, $object, $path, $removeIfNotSet);
                        }
                    })->withSubAttributes(
                        new Constant('value', 'email')->ensure('string'),
                        new Constant('type', 'other'),
                        new Constant('primary', true)->ensure('boolean'),
                    )->ensure('array')
                        ->setMultiValued(true),

                    // addresses chonk
                    (new class ('addresses') extends Complex {
                        static $addressmap = [
                            'streetAddress' => 'address',
                            'locality' => 'city',
                            'region' => 'state',
                            'postalCode' => 'zip',
                            'country' => 'country'
                        ];

                        protected function doRead(&$object, $attributes = [])
                        {
                            $address = [];
                            foreach (self::$addressmap as $scim_field => $db_field) {
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

                        public function patch($operation, $value, Model &$object, Path $path = null, $removeIfNotSet = false)
                        {
                            if ($path->getValuePathFilter() != null) {
                                \Log::error("path for update $path");
                                // get the part of the $path that we actually care about - something like:
                                // addresses[type eq "work"]
                                $matches = null;
                                if (!preg_match('/^.+\[type eq "([a-zA-Z]+)"](?:\.([a-zA-Z]+))?$/', (string)$path, $matches)) {
                                    throw new \Exception("Unknown path type '$path'");
                                }
                                $type = $matches[1];
                                if ($type != 'work') {
                                    throw new \Exception("Unknown object type '$type'");
                                }
                                $attribute = array_key_exists(2, $matches) ? $matches[2] : null;
                                if (array_key_exists($attribute, self::$addressmap)) {
                                    $object->{self::$addressmap[$attribute]} = $value;
                                    return;
                                }


                                throw new \Exception("path for update $path");
                            }
                        }

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
                                \Log::error("Checking to see if our 'doRead' even gets a chance to get called?");
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
                    new MappedTable('department', 'department', Department::class, 'department_id', 'name'),
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

                        public function add($value, Model &$object)
                        {
                            \Log::error("What type of value is value? " . gettype($value));
                            if (is_scalar($value)) {
                                \Log::error("Weird Microsoft mode - set manager to the \$value and move on with life?");
                                $object->manager_id = $value;
                            } else {
                                //FIXME - do this properly
                                \Log::error("Non-Microsoft - Trying to 'ADD' for maanger with value: " . print_r($value, true));
                                throw new \Exception("dunno how to do this (add manager)");
                            }
                        }

                        // TODO - we keep repeating ourselves between add/replace, we should maybe make our own class
                        // to make this a nicer shorthand?
                        public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
                        {
                            \Log::error("What type of value is value? " . gettype($value));
                            if (is_scalar($value)) {
                                \Log::error("Weird Microsoft mode - set manager to the \$value and move on with life?");
                                $object->manager_id = $value;
                            } else {
                                //FIXME - actualy do this? (Try on one of the other platforms)
                                \Log::error("Non-Microsoft - Trying to 'ADD' for maanger with value: " . print_r($value, true));
                                throw new \Exception("dunno how to do this (add manager)");
                            }
                        }
                    }) // ->withSubAttributes() ... -> ensure() ?
                ),
                (new AttributeSchema(self::GROKABILITY, false))->withSubAttributes(
                    new MappedTable('location', 'location', Location::class, 'location_id', 'name'),
                    new MappedTable('company', 'company', Company::class, 'company_id', 'name'),
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
                eloquent('externalId', 'scim_externalid'),
                new Meta('Groups'),
                (new AttributeSchema(Schema::SCHEMA_GROUP, true))->withSubAttributes(
                    eloquent('displayName', 'name')->ensure('required', 'min:3', function ($attribute, $value, $fail) {
                        // check if group does not exist or if it exists, it is the same group
                        $group = $this->getGroupClass()::where('name', $value)->first();
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
