<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

HTMLHelper::_('bootstrap.framework');

$user = Factory::getUser();

// Run diagnostics
$helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
$diagnostics = $helper->getConnectionDiagnostics();
?>

<div class="cotizaciones-component">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Diagnóstico de Conexión Odoo</h1>
            <div class="user-info">
                <small class="text-muted">
                    Usuario: <?php echo htmlspecialchars($user->name); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Configuration Summary -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-cog"></i> Configuración Actual</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>URL Base:</strong></td>
                            <td><code><?php echo htmlspecialchars($diagnostics['config']['base_url']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Base de Datos:</strong></td>
                            <td><code><?php echo htmlspecialchars($diagnostics['config']['database']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Usuario:</strong></td>
                            <td><code><?php echo htmlspecialchars($diagnostics['config']['username']); ?></code></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>API Key Configurada:</strong></td>
                            <td>
                                <?php if ($diagnostics['config']['password_set']): ?>
                                    <span class="badge bg-success">Sí (<?php echo $diagnostics['config']['password_length']; ?> caracteres)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Estado Final:</strong></td>
                            <td>
                                <?php if ($diagnostics['success']): ?>
                                    <span class="badge bg-success">Conexión Exitosa</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Conexión Fallida</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Paso Actual:</strong></td>
                            <td><code><?php echo htmlspecialchars($diagnostics['step']); ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Results -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Resultados de Pruebas</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Pruebas de Conectividad</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            cURL Disponible
                            <?php if (isset($diagnostics['tests']['curl_available']) && $diagnostics['tests']['curl_available']): ?>
                                <span class="badge bg-success">✓</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Conectividad Básica
                            <?php if (isset($diagnostics['tests']['basic_connectivity']) && $diagnostics['tests']['basic_connectivity']): ?>
                                <span class="badge bg-success">✓</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Endpoint Common
                            <?php if (isset($diagnostics['tests']['common_endpoint']) && $diagnostics['tests']['common_endpoint']): ?>
                                <span class="badge bg-success">✓</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Pruebas de Funcionalidad</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Autenticación
                            <?php if (isset($diagnostics['tests']['authentication']) && $diagnostics['tests']['authentication']): ?>
                                <span class="badge bg-success">✓ (UID: <?php echo $diagnostics['tests']['uid'] ?? 'N/A'; ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Endpoint Object
                            <?php if (isset($diagnostics['tests']['object_endpoint']) && $diagnostics['tests']['object_endpoint']): ?>
                                <span class="badge bg-success">✓</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Búsqueda de Cotizaciones
                            <?php if (isset($diagnostics['tests']['quote_search']) && $diagnostics['tests']['quote_search']): ?>
                                <span class="badge bg-success">✓ (<?php echo $diagnostics['tests']['quotes_found'] ?? 0; ?> encontradas)</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Responses -->
    <?php if (isset($diagnostics['tests']['common_response'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Respuesta del Endpoint Common</h6>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars(print_r($diagnostics['tests']['common_response'], true)); ?></code></pre>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($diagnostics['tests']['object_response'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="fas fa-database"></i> Respuesta del Endpoint Object</h6>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars(print_r($diagnostics['tests']['object_response'], true)); ?></code></pre>
        </div>
    </div>
    <?php endif; ?>

    <!-- Errors -->
    <?php if (!empty($diagnostics['errors'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Errores Detectados</h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <?php foreach ($diagnostics['errors'] as $error): ?>
                <li class="list-group-item list-group-item-danger">
                    <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($error); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Recomendaciones</h5>
        </div>
        <div class="card-body">
            <?php if (!$diagnostics['success']): ?>
                <div class="alert alert-warning">
                    <h6>Para solucionar los problemas de conexión:</h6>
                    <ol>
                        <li><strong>Verificar credenciales:</strong> Asegúrate de que el usuario y API key sean correctos</li>
                        <li><strong>Verificar URL:</strong> Confirma que la URL de Odoo sea accesible desde tu servidor</li>
                        <li><strong>Verificar permisos:</strong> El usuario debe tener acceso al modelo 'sale.order'</li>
                        <li><strong>Verificar firewall:</strong> Tu servidor debe poder conectarse a Odoo</li>
                        <li><strong>Probar manualmente:</strong> Intenta acceder a <?php echo htmlspecialchars($diagnostics['config']['base_url']); ?>/xmlrpc/2/common desde tu navegador</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h6>¡Conexión exitosa!</h6>
                    <p>Tu conexión a Odoo está funcionando correctamente. Puedes proceder a usar el componente normalmente.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="text-center">
        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Volver a Cotizaciones
        </a>
        <button onclick="window.location.reload()" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i> Ejecutar Diagnóstico Nuevamente
        </button>
        <?php if (Factory::getUser()->authorise('core.admin')): ?>
        <a href="<?php echo Route::_('administrator/index.php?option=com_cotizaciones&view=config'); ?>" class="btn btn-warning">
            <i class="fas fa-cog"></i> Configurar Componente
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    font-weight: 600;
}

pre code {
    font-size: 0.85rem;
    max-height: 300px;
    overflow-y: auto;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.badge {
    font-size: 0.8rem;
}
</style>