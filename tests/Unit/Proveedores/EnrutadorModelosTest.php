<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Proveedores;

use Brain\Monkey\Functions;
use Pluma\Proveedores\EnrutadorModelos;
use Pluma\Proveedores\PropositoLenguaje;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Proveedores\EnrutadorModelos
 */
final class EnrutadorModelosTest extends CasoDePruebaUnitario {

	public function test_usa_el_modelo_economico_por_defecto_para_un_proposito_no_premium(): void {
		Functions\when( 'get_option' )->justReturn( 'anthropic/claude-haiku-4.5' );

		self::assertSame(
			'anthropic/claude-haiku-4.5',
			( new EnrutadorModelos() )->modeloPara( PropositoLenguaje::Clasificar )
		);
	}

	public function test_usa_el_modelo_premium_por_defecto_para_redactar(): void {
		Functions\when( 'get_option' )->justReturn( 'anthropic/claude-sonnet-5' );

		self::assertSame(
			'anthropic/claude-sonnet-5',
			( new EnrutadorModelos() )->modeloPara( PropositoLenguaje::Redactar )
		);
	}

	public function test_respeta_el_modelo_configurado_por_el_cliente(): void {
		Functions\when( 'get_option' )->alias(
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInTernaryCondition.FoundInTernaryCondition -- falso positivo: es una arrow function con `=>`, no una asignación.
			static fn ( string $opcion, $defecto = false ) => EnrutadorModelos::OPCION_MODELO_PREMIUM === $opcion
				? 'openai/gpt-5.2'
				: $defecto
		);

		self::assertSame(
			'openai/gpt-5.2',
			( new EnrutadorModelos() )->modeloPara( PropositoLenguaje::Corregir )
		);
	}

	public function test_ignora_una_opcion_vacia_y_cae_al_defecto(): void {
		Functions\when( 'get_option' )->alias(
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- la firma debe calzar con la de get_option(); el doble ignora ambos parámetros a propósito.
			static fn ( string $opcion, $defecto = false ) => ''
		);

		self::assertSame(
			'anthropic/claude-haiku-4.5',
			( new EnrutadorModelos() )->modeloPara( PropositoLenguaje::Titulares )
		);
	}
}
