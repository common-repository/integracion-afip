<?php

namespace CRPlugins\Afip\Rest;

defined( 'ABSPATH' ) || exit;

interface RestRouteInterface {
	public function register_routes(): void;
}
