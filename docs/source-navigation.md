# Find the owning source and tests

Use the [assistant guidance index](../.agents/README.md) for skill selection. Start with the relevant row when tracing a behavior change.

| Area | Source | Verification |
| --- | --- | --- |
| HTTP and host boundaries | [Routes](../routes/web.php), [application bootstrap](../bootstrap/app.php), [error response](../app/Support/ApiError.php) | [Host behavior](../tests/Feature/PublicWebsiteTest.php), [OpenAPI responses](../tests/Contract/OpenApiTest.php), [Swagger delivery](../tests/Feature/ApiDocumentationTest.php) |
| Identity and access | [Sign-in](../app/Http/Controllers/AuthController.php), [sessions](../app/Support/AdministratorSession.php), [access](../app/Support/Access.php), [roles](../app/Http/Controllers/RoleController.php) | [Authentication](../tests/Feature/AuthenticationTest.php), [roles and users](../tests/Feature/UserRoleWorkflowTest.php), [permissions and content](../tests/Feature/AccessAndContentTest.php) |
| Publishing and public pages | [Projects](../app/Http/Controllers/ProjectController.php), [publishing](../app/Actions/Projects/PublishProject.php), [ordering](../app/Actions/Projects/ReorderProjects.php), [catalog queries](../app/Queries/Projects/ProjectCatalog.php), [articles](../app/Http/Controllers/ArticleController.php), [public views](../resources/views/public) | [Editorial workflow](../tests/Feature/EditorialWorkflowTest.php), [browser checks](../tests/browser/public.spec.ts) |
| Media and stored data | [Media lifecycle](../app/Support/MediaLifecycle.php), [upload validation](../app/Support/UploadedMedia.php), [migrations](../database/migrations), [shared write transaction](../app/Support/WriteTransaction.php) | [Media](../tests/Feature/MediaTest.php), [migrations](../tests/Feature/MigrationTest.php), [concurrent locking](../tests/Feature/WriteTransactionTest.php) |
| Request logging and operations | [Request middleware](../app/Http/Middleware/TrackRequest.php), [logging guide](operations-logging.md), [deployment](../DEPLOY.md) | [Request tracking](../tests/Feature/RequestTrackingTest.php), [release package verification](../scripts/verify-release.php), [CI](../.github/workflows/ci.yml) |
