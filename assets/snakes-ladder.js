document.addEventListener('DOMContentLoaded', function () {
    const players = document.querySelectorAll('.player');

    // Highlight a player when clicked
    players.forEach(player => {
        player.addEventListener('click', function () {
            // Remove existing highlights
            players.forEach(p => p.classList.remove('highlight'));

            // Highlight the clicked player
            this.classList.add('highlight');

            // Remove highlight after 3 seconds
            setTimeout(() => this.classList.remove('highlight'), 3000);
        });
    });

    // Calculate square positions
    function calculateSquarePositions(boardSize) {
        const positions = [];
        const squareSize = 600 / boardSize; // Assuming board is 600x600px

        for (let row = 0; row < boardSize; row++) {
            for (let col = 0; col < boardSize; col++) {
                positions.push({
                    x: col * squareSize,
                    y: row * squareSize
                });
            }
        }
        return positions;
    }

    // Fetch player data
    fetch(snakesLaddersAjax.ajax_url + '?action=get_player_data')
        .then(response => response.json())
        .then(players => {
            const squarePositions = calculateSquarePositions(10);
            players.forEach(player => {
                const token = document.createElement('div');
                token.classList.add('player-marker');
                token.style.left = `${squarePositions[player.current_position - 1].x}px`;
                token.style.top = `${squarePositions[player.current_position - 1].y}px`;
                document.querySelector('.snakes-ladders-board').appendChild(token);
            });
        });
});
