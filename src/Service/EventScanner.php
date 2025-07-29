<?php

declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\Service;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

class EventScanner
{
    private array $scanDirectories;
    private array $excludePatterns;

    public function __construct(array $scanDirectories, array $excludePatterns)
    {
        $this->scanDirectories = $scanDirectories;
        $this->excludePatterns = $excludePatterns;
    }

    public function scanEvents(): array
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $finder = new Finder();
        $nodeFinder = new NodeFinder();

        $events = [];

        foreach ($this->scanDirectories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $finder->files()->in($directory)->name('*.php');

            foreach ($this->excludePatterns as $pattern) {
                $finder->notPath($pattern);
            }

            foreach ($finder as $file) {
                try {
                    $code = $file->getContents();
                    $ast = $parser->parse($code);

                    if ($ast === null) {
                        continue;
                    }

                    // Find event dispatches
                    $eventDispatches = $this->findEventDispatches($nodeFinder, $ast);

                    // Find event class definitions
                    $eventClasses = $this->findEventClasses($nodeFinder, $ast);

                    if (!empty($eventDispatches) || !empty($eventClasses)) {
                        $events[$file->getPathname()] = [
                            'dispatches' => $eventDispatches,
                            'classes' => $eventClasses,
                        ];
                    }
                } catch (\Throwable $e) {
                    // Skip files that can't be parsed
                    continue;
                }
            }
        }

        return $events;
    }

    private function findEventDispatches(NodeFinder $nodeFinder, array $ast): array
    {
        $dispatches = [];

        // Find method calls to dispatch() or similar
        $methodCalls = $nodeFinder->findInstanceOf($ast, Node\Expr\MethodCall::class);

        foreach ($methodCalls as $call) {
            if ($call->name instanceof Node\Identifier) {
                $methodName = $call->name->name;

                if (in_array($methodName, ['dispatch', 'dispatchEvent'], true)) {
                    $eventData = $this->extractEventFromCall($call);
                    if ($eventData) {
                        $dispatches[] = $eventData;
                    }
                }
            }
        }

        return $dispatches;
    }

    private function findEventClasses(NodeFinder $nodeFinder, array $ast): array
    {
        $classes = [];

        $classNodes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);

        foreach ($classNodes as $classNode) {
            if ($this->isEventClass($classNode)) {
                $classes[] = [
                    'name' => $classNode->name->name,
                    'line' => $classNode->getLine(),
                ];
            }
        }

        return $classes;
    }

    private function extractEventFromCall(Node\Expr\MethodCall $call): ?array
    {
        if (!isset($call->args[0])) {
            return null;
        }

        $firstArg = $call->args[0]->value;
        $eventName = null;
        $eventClass = null;

        // Handle string event names
        if ($firstArg instanceof Node\Scalar\String_) {
            $eventName = $firstArg->value;
        }

        // Handle new Class() instances
        if ($firstArg instanceof Node\Expr\New_) {
            if ($firstArg->class instanceof Node\Name) {
                $eventClass = $firstArg->class->toString();
            }
        }

        // Handle variable references
        if ($firstArg instanceof Node\Expr\Variable) {
            $eventName = '$' . $firstArg->name;
        }

        return [
            'name' => $eventName,
            'class' => $eventClass,
            'line' => $call->getLine(),
        ];
    }

    private function isEventClass(Node\Stmt\Class_ $classNode): bool
    {
        // Check if class extends Event or implements EventInterface
        if ($classNode->extends) {
            $parentClass = $classNode->extends->toString();
            if (str_contains($parentClass, 'Event')) {
                return true;
            }
        }

        // Check interfaces
        foreach ($classNode->implements as $interface) {
            if (str_contains($interface->toString(), 'Event')) {
                return true;
            }
        }

        // Check class name patterns
        $className = $classNode->name->name;
        return str_ends_with($className, 'Event');
    }
}

