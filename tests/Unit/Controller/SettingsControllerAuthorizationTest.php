<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Controller\SettingsController;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class SettingsControllerAuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{string}> Controller actions.
     */
    public static function actionProvider(): array
    {
        return [
            'set profile'       => ['setTokenSet'],
            'deactivate profile' => ['deactivateTokenSet'],
            'get profile'       => ['getTokenSet'],
            'get theming plan'  => ['getThemingPlan'],
            'rollback profile'  => ['rollbackTokenSet'],
            'get history'       => ['getProfileHistory'],
        ];
    }

    #[DataProvider('actionProvider')]
    public function testActionRequiresAuthorizedAdminSetting(string $methodName): void
    {
        $method     = new ReflectionMethod(SettingsController::class, $methodName);
        $attributes = $method->getAttributes(AuthorizedAdminSetting::class);

        self::assertCount(1, $attributes);
        self::assertSame(Admin::class, $attributes[0]->newInstance()->getSettings());
    }

    public function testEveryDeclaredPublicActionIsCoveredByAuthorizationTest(): void
    {
        $controller = new ReflectionClass(SettingsController::class);
        $actions    = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $controller->getMethods(ReflectionMethod::IS_PUBLIC),
                static fn (ReflectionMethod $method): bool =>
                    $method->isConstructor() === false
                    && $method->getDeclaringClass()->getName() === SettingsController::class
            )
        );
        $coveredActions = array_column(self::actionProvider(), 0);

        sort($actions);
        sort($coveredActions);

        self::assertSame($coveredActions, $actions);
    }

    public function testRouteTableContainsOnlyAuditedTypedActions(): void
    {
        /** @var array{routes: array<int, array{name: string, url: string, verb: string}>} $routes */
        $routes = require dirname(__DIR__, 3).'/appinfo/routes.php';

        self::assertSame(
            [
                ['name' => 'settings#setTokenSet', 'url' => '/settings/tokenset', 'verb' => 'POST'],
                ['name' => 'settings#deactivateTokenSet', 'url' => '/settings/deactivate', 'verb' => 'POST'],
                ['name' => 'settings#getTokenSet', 'url' => '/settings/tokenset', 'verb' => 'GET'],
                ['name' => 'settings#getThemingPlan', 'url' => '/settings/theming-plan', 'verb' => 'GET'],
                ['name' => 'settings#rollbackTokenSet', 'url' => '/settings/rollback', 'verb' => 'POST'],
                ['name' => 'settings#getProfileHistory', 'url' => '/settings/profile-history', 'verb' => 'GET'],
            ],
            $routes['routes']
        );
    }

    public function testDelegatedConfigPermissionIsLimitedToProfileState(): void
    {
        $admin = (new ReflectionClass(Admin::class))->newInstanceWithoutConstructor();

        self::assertSame(
            [
                Application::APP_ID => [
                    '/^(active_profile_state|active_profile_revision|profile_state_history|token_set)$/',
                ],
            ],
            $admin->getAuthorizedAppConfig()
        );
    }

    public function testProfileRevisionIsRequiredAndStrictlyFormatted(): void
    {
        $controller = (new \ReflectionClass(SettingsController::class))
            ->newInstanceWithoutConstructor();
        $validator  = new ReflectionMethod(
            SettingsController::class,
            'isValidExpectedRevision'
        );

        self::assertFalse($validator->invoke($controller, ''));
        self::assertFalse($validator->invoke($controller, 'ABCDEF0123456789ABCD'));
        self::assertFalse($validator->invoke($controller, 'too-short'));
        self::assertTrue($validator->invoke($controller, 'abcdef0123456789abcd'));
    }
}
