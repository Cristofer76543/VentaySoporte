<?php
include('conexion.php');

// --- Lógica para manejar la solicitud AJAX de precios (GET) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_price') {
    $type = $_GET['type'] ?? ''; // Usar el operador de fusión de null para evitar advertencias
    $price = 0;

    if ($type === 'resistencia') {
        $sql = "SELECT precio FROM termyconec WHERE categoria = 'resistencia' LIMIT 1";
    } elseif ($type === 'conector') {
        $sql = "SELECT precio FROM termyconec WHERE categoria = 'conector' LIMIT 1";
    } elseif ($type === 'capacitor') {
        $sql = "SELECT precio FROM termyconec WHERE categoria = 'capacitor' LIMIT 1";
    } elseif ($type === 'terminal') {
        $sql = "SELECT precio FROM termyconec WHERE categoria = 'terminal' LIMIT 1";
    } else {
        echo json_encode(["success" => false, "error" => "Tipo de componente no válido"]);
        exit;
    }

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $price = $row['precio'];
        echo json_encode(["success" => true, "price" => $price]);
    } else {
        echo json_encode(["success" => false, "error" => "No se encontró el precio"]);
    }
    $conn->close();
    exit; // Termina la ejecución solo para esta respuesta AJAX
}

//lanzamineot de producto personalizado sin config
if (isset($_GET['action']) && $_GET['action'] === 'get_custom_product') {
    $tipo = $_GET['tipo'] ?? '';

    if (!$tipo) {
        echo json_encode(["success" => false, "error" => "Parámetro tipo es requerido"]);
        exit;
    }

    $tipo = $conn->real_escape_string($tipo);

    $sql = "SELECT id, descripcion, precio, imagen FROM productos_personalizados WHERE tipo = '$tipo' LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "product" => [
                "id" => $row['id'],
                "descripcion" => $row['descripcion'],
                "precio" => floatval($row['precio']),
                "imagen" => $row['imagen']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Producto personalizado no encontrado"]);
    }
    $conn->close();
    exit;
}
// --- Lógica normal de la página (POST/GET común) ---

// Consulta SQL
$sql_placasyprotoboards = "SELECT id, nombre, descripcion, precio, imagen, cantidad, 'placasyprotoboards' as categoria FROM placasyprotoboards";
$sql_electronicabasica = "SELECT id, nombre, descripcion, precio, imagen, cantidad, 'electronicabasica' as categoria FROM electronicabasica";
$sql_telecomunicaciones = "SELECT id, nombre, descripcion, precio, imagen, cantidad, 'telecomunicaciones' as categoria FROM telecomunicaciones";

$sql_combined = $sql_placasyprotoboards . " UNION ALL " . $sql_electronicabasica . " UNION ALL " . $sql_telecomunicaciones;

$result_combined = $conn->query($sql_combined);

// Verificamos si la consulta fue exitosa
if ($result_combined === false) {
    die("Error en la consulta: " . $conn->error);
}

// Mostrar productos
function displayProducts($category, $result_combined)
{
    if ($result_combined === null || $result_combined === false) {
        echo "<section id=\"$category\"><h2>" . htmlspecialchars(ucfirst($category)) . "</h2><p>Error al cargar los productos.</p></section>";
        return;
    }

    $result_combined->data_seek(0);
    $hasProducts = false;

    while ($row = $result_combined->fetch_assoc()) {
        if (strtolower($row['categoria']) === $category && $row['cantidad'] > 0) {
            if (!$hasProducts) {
                echo "<section id=\"$category\">";
                echo "<h2>" . htmlspecialchars(ucfirst($category)) . "</h2>";
                echo "<div class=\"product-container\">";
                $hasProducts = true;
            }

            echo "<div class=\"product\" id=\"product-{$row['categoria']}-{$row['id']}\">";
            echo "<img src=\"Imagenes/$category/" . htmlspecialchars(basename($row['imagen'])) . "\" alt=\"" . htmlspecialchars($row['nombre']) . "\" style=\"width:100%; max-width:150px; height:auto;\">"; // Ajuste de tamaño
            echo "<h2>" . htmlspecialchars($row['nombre']) . "</h2>";
            echo "<p>" . htmlspecialchars($row['descripcion']) . "</p>";
            echo "<p><strong>Precio:</strong> $" . number_format($row['precio'], 2) . "</p>";
            echo "<p><strong>Cantidad disponible:</strong> {$row['cantidad']}</p>";
            echo "<label>Cantidad: <input type=\"number\" id=\"quantity-input-{$row['categoria']}-{$row['id']}\" min=\"1\" max=\"{$row['cantidad']}\" value=\"1\"></label>";

            // Botón "Añadir al carrito"
            echo "<button class=\"add-to-cart\" data-id=\"{$row['id']}\" data-category=\"{$row['categoria']}\" data-uniqueid=\"{$row['categoria']}-{$row['id']}\" data-price=\"{$row['precio']}\" data-quantity=\"{$row['cantidad']}\" data-name=\"{$row['nombre']}\">Añadir al carrito</button>";
            echo "</div>";
        }
    }

    // Salida si no hay productos 
    if ($hasProducts) {
        echo "</div></section>";
    } else {
        echo "<section id=\"$category\"><h2>" . htmlspecialchars(ucfirst($category)) . "</h2><p>No hay productos disponibles.</p></section>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Componentes Electrónicos</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="notification" id="notification"></div>
    <div class="container">
        <!-- Sidebar de categorías -->
        <div class="sidebar">
            <h2>Categorías</h2>
            <ul>
                <li class="category-item" data-category="placasyprotoboards">
                    <img src="Imagenes/logos/targetasyprotos.png" alt="ProtoBoards y Tarjetas de Desarrollo">
                    <span>ProtoBoards y Tarjetas de Desarrollo</span>
                </li>
                <li class="category-item" data-category="electronicabasica">
                    <img src="Imagenes/logos/elecbasic.png" alt="Electrónica Básica">
                    <span>Electrónica Básica</span>
                </li>
                <li class="category-item" data-category="telecomunicaciones">
                    <img src="Imagenes/logos/termiyconec.png" alt="Terminales y Conectores">
                    <span>Material de telecomunic.</span>
                </li>
            </ul>
            <button id="open-custom-modal" class="add-custom-product-btn">
                + Terminales, Resistencias y Capacitores
            </button>
            <button id="open-soportetecnicopagina" class="add-custom-product-btn"
                onclick="window.open('Soporte tecnico/index.html', '_blank');">
                + Servicio de soporte técnico
            </button>
            <button id="open-internet" class="add-custom-product-btn"
                onclick="window.open('https://rubicknet.com/', '_blank');">
                + Servicio de internet
            </button>
            <div style="margin-top: auto; padding-top: 20px;">
                <button id="suggestion-button">Quejas y Sugerencias</button>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Carrito -->
            <button class="cart-button">
                <img src="Imagenes/logos/carrito.png" alt="Carrito de compras" class="cart-icon">
            </button>
            <div class="cart-overlay" id="cart-overlay"></div>
            <div class="cart-popup" id="cart-popup">
                <h2>Tu Carrito</h2>
                <div class="cart-header">
                    <div class="cart-product-name">Producto</div>
                    <div class="cart-product-price">Precio</div>
                    <div class="cart-product-quantity">Cantidad</div>
                    <div class="cart-product-total">Subtotal</div>
                </div>
                <div id="cart-items"></div>
                <div class="cart-footer">
                    <div>Total:</div>
                    <div>$<span id="cart-total">0.00</span></div>
                </div>
                <div class="cart-footer-buttons">
                    <button id="clear-cart" class="clear-cart-button">Limpiar Carrito</button>
                    <button id="checkout" class="checkout-button">Proceder con Compra</button>
                </div>
                <button id="close-cart" class="close-button">×</button>
            </div>
            <main>
                <?php
                // Mostrar productos solo si la consulta fue exitosa
                if ($result_combined !== false && $result_combined !== null) {
                    $categories = ['placasyprotoboards', 'electronicabasica', 'telecomunicaciones'];
                    foreach ($categories as $category) {
                        displayProducts($category, $result_combined);
                    }
                }
                ?>
            </main>
        </div>
    </div>

    <div id="suggestion-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Quejas y Sugerencias</h2>
            <form id="suggestion-form">
                <textarea id="suggestion-text" rows="4" placeholder="Escribe tu sugerencia aquí..." required></textarea>
                <button type="submit">Enviar</button>
            </form>
        </div>
    </div>

    <!-- Modal para productos personalizados -->
    <div id="custom-product-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="close-custom-modal">&times;</span>
            <h2>Añadir Producto Personalizado</h2>

            <div class="modal-body">
                <!-- Columna izquierda: información del producto -->
                <div class="product-info">
                    <h3>Vista previa</h3>
                    <div id="product-preview">
                        <p>Selecciona un componente para ver los detalles aquí.</p>
                    </div>
                </div>

                <!-- Columna derecha: formulario -->
                <div class="product-form">
                    <form id="custom-product-form">
                        <label for="component-type">Selecciona el tipo de componente:</label>
                        <select id="component-type" name="component-type" required>
                            <option value="">--Seleccionar--</option>
                            <option value="resistencia">Resistencia</option>
                            <option value="conector">Terminal</option>
                            <option value="capacitor">Capacitor</option>
                            <option value="terminal">Conector tipo Wago</option>
                        </select>

                        <div id="resistencia-options" class="config-section" style="display: none;">
                            <label for="resistencia-value">Valor de Resistencia (Ohmios):</label>
                            <input type="number" id="resistencia-value" name="resistencia-value" placeholder="Ej: 220"
                                required>
                            <label for="resistencia-unit">Unidad:</label>
                            <select id="resistencia-unit" name="resistencia-unit" required>
                                <option value="Ω">Ω</option>
                                <option value="KΩ">KΩ</option>
                            </select>
                        </div>

                        <div id="conector-options" class="config-section" style="display: none;">
                            <label for="conector-color">Color del Terminal:</label>
                            <select id="conector-color" name="conector-color" required>
                                <option value="rojo">Rojo</option>
                                <option value="azul">Azul</option>
                                <option value="amarillo">Amarillo</option>
                            </select>
                            <label for="vias-conector">Tipo de terminal:</label>
                            <input type="number" id="vias-conector" name="vias-conector" placeholder="Ej: 2" min="1"
                                required>
                        </div>

                        <div id="capacitor-options" class="config-section" style="display: none;">
                            <label for="capacitor-value">Valor de Capacitor (uF):</label>
                            <input type="number" id="capacitor-value" name="capacitor-value" placeholder="Ej: 10"
                                required>
                        </div>

                        <div id="terminal-options" class="config-section" style="display: none;">
                            <label for="terminal-type">Número de Vías de Conector Tipo Wago:</label>
                            <select id="terminal-type" name="terminal-type" required>
                                <option value="tipo1">2 vías</option>
                                <option value="tipo2">3 vías</option>
                                <option value="tipo3">5 vías</option>
                            </select>
                        </div>

                        <label for="custom-quantity">Cantidad:</label>
                        <input type="number" id="custom-quantity" name="custom-quantity" placeholder="Cantidad" min="1"
                            value="1" required>

                        <button type="submit">Agregar al carrito</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="script.js"></script>
</body>

</html>
<?php
$conn->close();
?>