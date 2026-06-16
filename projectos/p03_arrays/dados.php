<?php
declare(strict_types=1);

/**
 * Devolve uma lista de produtos em bruto, como viria de uma query SQL.
 * Cada produto tem campos que podem estar sujos: preços como string,
 * stock como string, categoria com espaços, activo como 0/1.
 *
 * @return array<int, array<string, mixed>>
 */

function obterProdutosBrutos(): array{
    return [
        ['id' => 1, 'nome' => 'Laptop Pro',      'preco' => '1200.00', 'stock' => '5',  'categoria' => ' electrónica ',  'activo' => 1],
        ['id' => 2, 'nome' => 'Rato Sem Fios',   'preco' => '25.99',   'stock' => '0',  'categoria' => ' electrónica ',  'activo' => 1],
        ['id' => 3, 'nome' => 'Secretária',       'preco' => '350.00',  'stock' => '3',  'categoria' => ' mobiliário  ',  'activo' => 1],
        ['id' => 4, 'nome' => 'Cadeira Ergon.',   'preco' => '280.00',  'stock' => '7',  'categoria' => ' mobiliário  ',  'activo' => 0],
        ['id' => 5, 'nome' => 'Monitor 4K',       'preco' => '699.99',  'stock' => '2',  'categoria' => ' electrónica ',  'activo' => 1],
        ['id' => 6, 'nome' => 'Teclado Mec.',     'preco' => '89.90',   'stock' => '12', 'categoria' => ' electrónica ',  'activo' => 1],
        ['id' => 7, 'nome' => 'Candeeiro LED',    'preco' => '45.00',   'stock' => '0',  'categoria' => ' iluminação  ',  'activo' => 1],
        ['id' => 8, 'nome' => 'Estante Madeira',  'preco' => '195.00',  'stock' => '4',  'categoria' => ' mobiliário  ',  'activo' => 1],
    ];
}
