<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'snakes_ladders_add_admin_menu');
function snakes_ladders_add_admin_menu() {
    add_menu_page(
        'Snakes and Ladders',
        'Snakes and Ladders',
        'manage_options',
        'snakes-ladders-game',
        'snakes_ladders_admin_page',
        'dashicons-gamepad',
        20
    );
}

// Admin page content
function snakes_ladders_admin_page() {
    global $wpdb;

    // Handle form submissions
    if ($_POST['action'] === 'add_player') {
        $wpdb->insert(
            $wpdb->prefix . 'snakes_ladders_players',
            ['name' => sanitize_text_field($_POST['player_name']), 'position' => 0] // Start at position 0
        );
    } elseif ($_POST['action'] === 'update_position') {
        $player_id = intval($_POST['player_id']);
        $dice_roll = intval($_POST['dice_roll']);

        // Update player position and apply snakes and ladders logic
        update_player_position($player_id, $dice_roll);
    }

    // Fetch all players
    $players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snakes_ladders_players");

    // Display form and player list
    ?>
    <div class="wrap">
        <h1>Snakes and Ladders Admin</h1>
        <form method="post">
            <input type="hidden" name="action" value="add_player">
            <input type="text" name="player_name" placeholder="Enter Player Name" required>
            <button type="submit" class="button button-primary">Add Player</button>
        </form>
        <h2>Players</h2>
        <table class="widefat fixed" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td><?php echo esc_html($player->name); ?></td>
                        <td><?php echo $player->position === '0' ? 'Off the Board' : esc_html($player->position); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="update_position">
                                <input type="hidden" name="player_id" value="<?php echo esc_attr($player->id); ?>">
                                <select name="dice_roll">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                                <button type="submit" class="button">Move</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode for displaying the game board and player list
add_shortcode('snakes_ladders_game', 'snakes_ladders_game_shortcode');
function snakes_ladders_game_shortcode() {
    global $wpdb;

    // Fetch all players
    $players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snakes_ladders_players");

    // Display the board and player list
    ob_start();
    ?>
    <div style="display: flex; gap: 20px;">
        <!-- Player List -->
        <div style="flex: 1; border: 1px solid #000; padding: 10px; border-radius: 5px;">
            <h2>Players</h2>
            <ul id="player-list" style="list-style: none; padding: 0;">
                <?php foreach ($players as $player): ?>
                    <li id="player-<?php echo esc_attr($player->id); ?>" style="margin-bottom: 10px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;" onclick="highlightPlayer(<?php echo esc_attr($player->id); ?>)">
                        <strong><?php echo esc_html($player->name); ?></strong><br>
                        Previous Position: <?php echo esc_html($player->previous_position ?? 0); ?><br>
                        Current Position: <?php echo esc_html($player->position); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Game Board -->
        <div style="flex: 2;">
            <h2>Current Board</h2>
            <?php echo do_shortcode('[snakes_ladders_board]'); ?>
        </div>
    </div>
    <script>
        function highlightPlayer(playerId) {
            const playerElement = document.getElementById(`player-${playerId}`);
            playerElement.style.backgroundColor = '#ff0';
            setTimeout(() => {
                playerElement.style.backgroundColor = '';
            }, 3000); // Highlight for 3 seconds
        }
    </script>
    <?php
    return ob_get_clean();
}

// Update player position with game logic
function update_player_position($player_id, $dice_roll) {
    global $wpdb;

    // Get current position
    $current_position = $wpdb->get_var(
        $wpdb->prepare("SELECT position FROM {$wpdb->prefix}snakes_ladders_players WHERE id = %d", $player_id)
    );

    // If the player is off the board, they must roll a 6 to enter
    if ($current_position == 0 && $dice_roll != 6) {
        return; // Player stays off the board
    } elseif ($current_position == 0 && $dice_roll == 6) {
        $current_position = 1; // Move to the first square
    } else {
        // Calculate new position
        $new_position = $current_position + $dice_roll;

        // Make sure the player doesn't go beyond 100
        if ($new_position > 100) {
            $new_position = $current_position; // Player stays in the same position
        }

        // Snakes and Ladders rules
        $snakes = [
            99 => 63,
            94 => 44,
            83 => 45,
            89 => 48,
            51 => 8,
            41 => 17,
        ];

        $ladders = [
            3 => 20,
            6 => 14,
            11 => 28,
            16 => 72,
            38 => 59,
            49 => 67,
            57 => 76,
            71 => 91,
            73 => 86,
            81 => 98,
        ];

        // Check if the new position lands on a snake or a ladder
        if (isset($ladders[$new_position])) {
            $new_position = $ladders[$new_position];
        } elseif (isset($snakes[$new_position])) {
            $new_position = $snakes[$new_position];
        }
    }

    // Update position in the database
    $wpdb->update(
        $wpdb->prefix . 'snakes_ladders_players',
        ['position' => $new_position],
        ['id' => $player_id]
    );
}

// Shortcode for displaying the board
add_shortcode('snakes_ladders_board', 'snakes_ladders_board_shortcode');
function snakes_ladders_board_shortcode() {
    global $wpdb;

    // Fetch all players
    $players = $wpdb->get_results("SELECT name, position FROM {$wpdb->prefix}snakes_ladders_players");

    // Map each square to its coordinates on the board image
    $square_positions = calculate_square_positions();

    // List of available token images and their colors
    $tokens = [
        'yellow' => ['file' => 'playertoken-yellow.png', 'color' => '#FFEB3B'],
        'darkblue' => ['file' => 'playertoken-darkblue.png', 'color' => '#00008B'],
        'green' => ['file' => 'playertoken-green.png', 'color' => '#4CAF50'],
        'lightblue' => ['file' => 'playertoken-lightblue.png', 'color' => '#ADD8E6'],
        'orange' => ['file' => 'playertoken-orange.png', 'color' => '#FF9800'],
        'pink' => ['file' => 'playertoken-pink.png', 'color' => '#FFC0CB'],
        'red' => ['file' => 'playertoken-red.png', 'color' => '#F44336'],
    ];

    // Assign a token color to each player (cycle through tokens)
    $player_tokens = [];
    $token_colors = array_keys($tokens);
    $i = 0;
    foreach ($players as $player) {
        $player_tokens[$player->name] = $token_colors[$i % count($token_colors)];
        $i++;
    }

    // Generate the board HTML
    $output = '<div style="position: relative; width: 600px; height: 600px; background: url(' . SNAKES_AND_LADDERS_PLUGIN_URL . 'assets/board.png) no-repeat center; background-size: contain; border: 2px solid #000;">';

    // Place players on the board
    foreach ($players as $player) {
        $token_color = $player_tokens[$player->name];
        $token_image = SNAKES_AND_LADDERS_PLUGIN_URL . 'assets/' . $tokens[$token_color]['file'];
        $background_color = $tokens[$token_color]['color'];
        $text_color = is_dark_color($background_color) ? '#FFF' : '#000';

        if (isset($square_positions[$player->position])) {
            $coords = $square_positions[$player->position];
            $output .= '<div style="position: absolute; top: ' . ($coords['top'] - 10) . 'px; left: ' . ($coords['left'] - 10) . 'px; text-align: center;">';
            $output .= '<div style="position: relative;">';
            $output .= '<div class="player-name" style="margin-bottom: 5px; font-size: 12px; font-weight: bold; background-color: ' . $background_color . '; color: ' . $text_color . '; padding: 2px 5px; border-radius: 4px;" onclick="this.style.opacity=1;">' . esc_html($player->name) . '</div>'; // Name above token
            $output .= '<img src="' . $token_image . '" style="width: 15px; height: 15px;">';
            $output .= '</div>';
            $output .= '</div>';
        }
    }

    $output .= '</div>';

    return $output;
}

// Utility function to determine if a color is dark or light
function is_dark_color($hex_color) {
    $r = hexdec(substr($hex_color, 1, 2));
    $g = hexdec(substr($hex_color, 3, 2));
    $b = hexdec(substr($hex_color, 5, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance < 0.5;
}

// Calculate the coordinates of each square on the board
function calculate_square_positions() {
    $positions = [];
    $square_size = 60; // Each square is 60x60 pixels
    $board_size = 10; // 10x10 grid

    for ($row = 0; $row < $board_size; $row++) {
        for ($col = 0; $col < $board_size; $col++) {
            $square_number = ($row % 2 === 0)
                ? ($row * $board_size) + $col + 1
                : ($row * $board_size) + ($board_size - $col);

            $positions[$square_number] = [
                'top' => (9 - $row) * $square_size,
                'left' => $col * $square_size,
            ];
        }
    }

    return $positions;
}