<?php

declare(strict_types=1);

namespace App\Lib\Helpers;

use Closure;
use Countable;
use Exception;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @implements \IteratorAggregate<int|string,string>
 */
final class ClassFinder implements IteratorAggregate, Countable
{
    /** @suppress  PHP0413 */
    /** The composer class loader as returned from `vendor/autoload.php` */
    protected ClassLoader $composer;

    /**
     * This will be filled with fully qualified class names as they are found
     * by searching through the various class maps provided by composer.
     * @var array|string[]
     */
    protected array $foundClasses = [];

    /** The namespace to filter by will be stored here.*/
    protected ?string $namespace = null;

    /** The interface name to filter by will be stored here */
    protected ?string $implements = null;

    /** The parent class to filter by will be stored here. */
    protected ?string $extends = null;

    /**
     * An optional custom filter method can be set.
     * Otherwise we will use the `defaultFilter` method in this class.
    */
    protected ?Closure $filter = null;

    public function __construct(ClassLoader $composer)
    {
        $this->composer = $composer;
    }

    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function implements(string $interface): static
    {
        $this->implements = $interface;
        return $this;
    }

    public function extends(string $parent): static
    {
        $this->extends = $parent;
        return $this;
    }

    public function filterBy(Closure $filter): static
    {
        if ($this->implements !== null || $this->extends != null) {
            throw new Exception(
                'Can not set a custom filter and filter ' .
                'by `implements` or `extends`!'
            );
        }

        $this->filter = $filter;
        return $this;
    }

    /**
     * @throws \Exception
     * @return array|string[]
     */
    public function search(): array
    {
        $this->foundClasses = [];

        if ($this->namespace === null) {
            throw new Exception('Namespace must be set!');
        }

        $this->searchClassMap();
        $this->searchPsrMaps();
        $this->runFilter();

        $this->namespace  = null;
        $this->implements = null;
        $this->extends    = null;

        return $this->foundClasses;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->search());
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    protected function searchClassMap(): void
    {
        foreach ($this->composer->getClassMap() as $fqcn => $file) {
            if ($this->is($fqcn)) {
                $this->foundClasses[realpath($file)] = $fqcn;
            }
        }
    }

    public function is(string $subject): bool
    {
        $pattern = "{$this->namespace}*";
        if ($subject === $pattern) {
            return true;
        }

        $quotedPattern    = preg_quote($pattern, '/');
        $replaceWildCards = str_replace('\*', '.*', $quotedPattern);
        return $this->regexMatch('^' . $replaceWildCards . '\z', $subject);
    }

    public function regexMatch(string $pattern, string $subject, string $delimiter = '/', string $options = ''): bool
    {
        // Build the expression
        $expression =
            $delimiter .
            $pattern .
            $delimiter .
            $options
        ;

        // Run the expression
        $result = preg_match($expression, $subject);

        // If no errors return true or false based on number of matches found.
        if ($result !== false) {
            return $result === 1;
        }

        return false;
    }

    protected function searchPsrMaps(): void
    {
        $prefixes = array_merge(
            $this->composer->getPrefixes(), //psr0
            $this->composer->getPrefixesPsr4()      //psr4
        );

        $namespace = rtrim((string) $this->namespace, '\\');

        $namespaceSegments = explode('\\', $namespace);

        foreach ($prefixes as $ns => $dirs) {
            $ns                  = rtrim((string) $ns, '\\');
            $longestCommonPrefix = $this->getLongestCommonPrefix($ns, $namespace);

            $foundSegments = explode('\\', $longestCommonPrefix);

            foreach ($foundSegments as $key => $segment) {
                if ((string) $namespaceSegments[$key] !== (string) $segment) {
                    continue 2;
                }
            }

            foreach ($dirs as $dir) {
                foreach ((new Finder())->in($dir)->files()->name('*.php') as $file) {
                    if ($file instanceof SplFileInfo) {
                        $fqcn = rtrim($file->getRelativePathname(), '.php');
                        $fqcn = str_replace('/', '\\', $fqcn);

                        $fqcn = !str_starts_with($fqcn, $ns) ? $ns . $fqcn : $fqcn;

                        // if (preg_match("/{$this->namespace}*/", $fqcn)) {
                        if ($this->is($fqcn)) {
                            $this->foundClasses[$file->getRealPath()] = $fqcn;
                        }
                    }
                }
            }
        }
    }

    protected function getLongestCommonPrefix(string $namespace, string $secondNamespace): string
    {
        $longestCommonPrefix = '';

        for ($i = 0; $i < min(strlen($namespace), strlen($secondNamespace)); $i++) {
            $char = substr($namespace, $i, 1);

            if ($char == substr($secondNamespace, $i, 1)) {
                $longestCommonPrefix .= $char;
            } else {
                break;
            }
        }

        return $longestCommonPrefix;
    }

    protected function runFilter(): void
    {
        foreach ($this->foundClasses as $file => $fqcn) {
            $reflectionClass = '';
            try {
                $reflectionClass = new \ReflectionClass($fqcn);
            } catch (\ReflectionException $e) {
                $result = false;
            }

            if ($this->filter === null) {
                $result = $this->defaultFilter($reflectionClass);
            } else {
                $result = call_user_func($this->filter, $reflectionClass);
            }

            if ($result === false) {
                unset($this->foundClasses[$file]);
            }
        }
    }

    /**
     * The default filter run by `runFilter()`.
     *
     * Further filters by  interface or parent class and also filters
     * out actual Interfaces, Abstract Classes and Traits.
     *
     * @param  \ReflectionClass $reflectionClass
     * @return bool
     */
    protected function defaultFilter(\ReflectionClass $reflectionClass): bool
    {
        if ($this->implements !== null) {
            return $reflectionClass->implementsInterface($this->implements) && !$reflectionClass->isInterface();
        }

        if ($this->extends !== null) {
            return $reflectionClass->isSubclassOf($this->extends) && !$reflectionClass->isAbstract();
        }

        return (!$reflectionClass->isInterface() && !$reflectionClass->isAbstract() && !$reflectionClass->isTrait());
    }
}
