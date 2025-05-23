<?php

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\ProvidedNameMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Mappers\StudlyCaseMapper;
use Spatie\LaravelData\Resolvers\NameMappersResolver;
use Spatie\LaravelData\Support\DataAttributesCollection;
use Spatie\LaravelData\Support\Factories\DataAttributesCollectionFactory;

function getAttributes(object $class): DataAttributesCollection
{
    return DataAttributesCollectionFactory::buildFromReflectionProperty((new ReflectionProperty($class, 'property')));
}

beforeEach(function () {
    $this->resolver = new NameMappersResolver();
});

it('can get an input and output mapper', function () {
    $attributes = getAttributes(new class () {
        #[MapInputName('input'), MapOutputName('output')]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new ProvidedNameMapper('input'),
        'outputNameMapper' => new ProvidedNameMapper('output'),
    ]);
});

it('can have no mappers', function () {
    $attributes = getAttributes(new class () {
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => null,
        'outputNameMapper' => null,
    ]);
});

it('can have a single map attribute', function () {
    $attributes = getAttributes(new class () {
        #[MapName('input', 'output')]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new ProvidedNameMapper('input'),
        'outputNameMapper' => new ProvidedNameMapper('output'),
    ]);
});

it('can overwrite a general map attribute', function () {
    $attributes = getAttributes(new class () {
        #[MapName('input', 'output'), MapInputName('input_overwritten')]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new ProvidedNameMapper('input_overwritten'),
        'outputNameMapper' => new ProvidedNameMapper('output'),
    ]);
});

it('can map an int', function () {
    $attributes = getAttributes(new class () {
        #[MapName(0, 3)]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new ProvidedNameMapper(0),
        'outputNameMapper' => new ProvidedNameMapper(3),
    ]);
});

it('can map a string', function () {
    $attributes = getAttributes(new class () {
        #[MapName('hello', 'world')]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new ProvidedNameMapper('hello'),
        'outputNameMapper' => new ProvidedNameMapper('world'),
    ]);
});

it('can map a mapper class', function () {
    $attributes = getAttributes(new class () {
        #[MapName(CamelCaseMapper::class, SnakeCaseMapper::class)]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new CamelCaseMapper(),
        'outputNameMapper' => new SnakeCaseMapper(),
    ]);
});

it('can have default mappers', function () {
    config()->set('data.name_mapping_strategy.input', CamelCaseMapper::class);
    config()->set('data.name_mapping_strategy.output', SnakeCaseMapper::class);

    $attributes = getAttributes(new class () {
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new CamelCaseMapper(),
        'outputNameMapper' => new SnakeCaseMapper(),
    ]);
});

it('input name mappers only work when no mappers are specified', function () {
    config()->set('data.name_mapping_strategy.input', CamelCaseMapper::class);
    config()->set('data.name_mapping_strategy.output', SnakeCaseMapper::class);

    $attributes = getAttributes(new class () {
        #[MapInputName(StudlyCaseMapper::class)]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new StudlyCaseMapper(),
        'outputNameMapper' => new SnakeCaseMapper(),
    ]);
});

it('output name mappers only work when no mappers are specified', function () {
    config()->set('data.name_mapping_strategy.input', CamelCaseMapper::class);
    config()->set('data.name_mapping_strategy.output', SnakeCaseMapper::class);

    $attributes = getAttributes(new class () {
        #[MapOutputName(StudlyCaseMapper::class)]
        public $property;
    });

    expect($this->resolver->execute($attributes))->toMatchArray([
        'inputNameMapper' => new CamelCaseMapper(),
        'outputNameMapper' => new StudlyCaseMapper(),
    ]);
});

it('can ignore certain mapper types', function () {
    $attributes = getAttributes(new class () {
        #[MapInputName('input'), MapOutputName(CamelCaseMapper::class)]
        public $property;
    });

    expect(NameMappersResolver::create([ProvidedNameMapper::class])
        ->execute($attributes))->toMatchArray([
        'inputNameMapper' => null,
        'outputNameMapper' => new CamelCaseMapper(),
    ]);
});
