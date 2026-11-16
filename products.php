<?php
/**
 * PRODUCT CATALOG
 *
 * shop.php displays this list, and checkout.php looks products up here by id.
 * That means the price saved with an order always comes from this file,
 * never from the browser cart (which a customer could edit).
 *
 * Each bag is keyed by its id: $products[4]['name'], $products[4]['price'], ...
 */

$products = [
    1 => [
        "id" => 1,
        "name" => "Moira Classic — Blush Pink",
        "category" => "handbags",
        "tag" => "Bestseller",
        "price" => 1299,
        "image" => "Moira_Pink.jpg",
        "dimensions" => "9.5\" × 7\" × 3.5\"",
        "material" => "Marikina Synthetic Leather",
        "description" => "Two-way structured handbag with top handles and detachable strap."
    ],
    2 => [
        "id" => 2,
        "name" => "Moira Classic — Sage Green",
        "category" => "handbags",
        "tag" => "New Arrival",
        "price" => 1299,
        "image" => "Moira_Green.jpg",
        "dimensions" => "9.5\" × 7\" × 3.5\"",
        "material" => "Marikina Synthetic Leather",
        "description" => "Soft pastel sage finish crafted for effortless day-to-night styling."
    ],
    3 => [
        "id" => 3,
        "name" => "Moira Classic — Powder Blue",
        "category" => "handbags",
        "tag" => "",
        "price" => 1299,
        "image" => "Moira_Blue.jpg",
        "dimensions" => "9.5\" × 7\" × 3.5\"",
        "material" => "Marikina Synthetic Leather",
        "description" => "Clean silhouette in serene powder blue with gold-tone hardware."
    ],
    4 => [
        "id" => 4,
        "name" => "Maureen Crescent Bag",
        "category" => "crossbody",
        "tag" => "Featured",
        "price" => 1150,
        "image" => "crossbody2.jpg",
        "dimensions" => "8\" × 5\" × 3\"",
        "material" => "Textured Leather & Woven Strap",
        "description" => "Curved modern crescent silhouette with adjustable wide shoulder strap."
    ],
    9 => [
        "id" => 9,
        "name" => "Maureen Crescent Bag — Cognac",
        "category" => "crossbody",
        "tag" => "",
        "price" => 1150,
        "image" => "crossbody1.jpg",
        "dimensions" => "8\" × 5\" × 3\"",
        "material" => "Textured Leather & Woven Strap",
        "description" => "The Maureen crescent bag in cognac with a contrasting woven strap."
    ],
    5 => [
        "id" => 5,
        "name" => "Life Begins After Coffee Tote",
        "category" => "totes",
        "tag" => "",
        "price" => 1450,
        "image" => "Totebag1.jpg",
        "dimensions" => "14\" × 16\"",
        "material" => "Cotton Canvas",
        "description" => "A reusable canvas tote featuring a coffee-inspired design."
    ],
    6 => [
        "id" => 6,
        "name" => "Be Yourself Tote",
        "category" => "totes",
        "tag" => "New Arrival",
        "price" => 1550,
        "image" => "Totebag2.jpg",
        "dimensions" => "14\" × 16\"",
        "material" => "Cotton Canvas",
        "description" => "A lightweight canvas tote with an original illustrated design."
    ],
    7 => [
        "id" => 7,
        "name" => "Good Things Take Time Tote",
        "category" => "totes",
        "tag" => "",
        "price" => 1450,
        "image" => "Totebag3.jpg",
        "dimensions" => "14\" × 16\"",
        "material" => "Cotton Canvas",
        "description" => "A roomy canvas tote with a cheerful printed message."
    ],
    8 => [
        "id" => 8,
        "name" => "Dream Big Tote",
        "category" => "totes",
        "tag" => "",
        "price" => 1550,
        "image" => "Totebag4.jpg",
        "dimensions" => "14\" × 16\"",
        "material" => "Cotton Canvas",
        "description" => "An everyday canvas tote with an uplifting printed design."
    ]
];
