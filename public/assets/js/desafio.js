(() => {
    'use strict';

    const form = document.querySelector('#form-desafio');
    if (!form) {
        return;
    }

    const selects = Array.from(form.querySelectorAll('.grade-select'));
    const undoButton = form.querySelector('#desfazer-grade');
    const redoButton = form.querySelector('#refazer-grade');
    const clues = Array.from(form.querySelectorAll('.dica-item'));
    const timer = document.querySelector('#cronometro-desafio');
    let applyingHistory = false;

    const capture = () => selects.map((select) => select.value);
    const history = [capture()];
    let historyIndex = 0;

    const findHouse = (category, information) => {
        const matches = selects.filter(
            (select) => Number(select.dataset.categoria) === category
                && select.value !== ''
                && Number(select.value) === information
        );
        return matches.length === 1 ? Number(matches[0].dataset.casa) : null;
    };

    const updateDuplicateOptions = () => {
        for (let category = 0; category < 5; category += 1) {
            const row = selects.filter((select) => Number(select.dataset.categoria) === category);
            const chosen = new Set(row.map((select) => select.value).filter((value) => value !== ''));
            row.forEach((select) => {
                Array.from(select.options).forEach((option) => {
                    option.disabled = option.value !== ''
                        && option.value !== select.value
                        && chosen.has(option.value);
                });
            });
        }
    };

    const updateClues = () => {
        clues.forEach((clue) => {
            const relation = clue.dataset.relacao;
            const house1 = findHouse(Number(clue.dataset.cat1), Number(clue.dataset.info1));
            let result = null;

            if (house1 !== null && relation === 'CERT') {
                result = house1 === Number(clue.dataset.valorFixo);
            } else if (house1 !== null) {
                const house2 = findHouse(Number(clue.dataset.cat2), Number(clue.dataset.info2));
                if (house2 !== null) {
                    if (relation === 'EQ') {
                        result = house1 === house2;
                    } else if (relation === 'M1') {
                        result = house1 + 1 === house2;
                    } else if (relation === 'PM1') {
                        result = Math.abs(house1 - house2) === 1;
                    }
                }
            }

            const status = clue.querySelector('.dica-status');
            const accessibleStatus = clue.querySelector('.dica-status-text');
            clue.classList.toggle('dica-satisfeita', result === true);
            clue.classList.toggle('dica-nao-satisfeita', result === false);
            status.textContent = result === true ? '✓' : (result === false ? '×' : '');
            accessibleStatus.textContent = result === true
                ? 'Dica satisfeita.'
                : (result === false ? 'Dica ainda não satisfeita.' : 'Ainda não verificada.');
        });
    };

    const updateButtons = () => {
        undoButton.disabled = historyIndex === 0;
        redoButton.disabled = historyIndex >= history.length - 1;
    };

    const refresh = () => {
        updateDuplicateOptions();
        updateClues();
        updateButtons();
    };

    const applyState = (state) => {
        applyingHistory = true;
        selects.forEach((select, index) => {
            select.value = state[index] ?? '';
        });
        applyingHistory = false;
        refresh();
    };

    selects.forEach((select) => {
        select.addEventListener('change', () => {
            if (applyingHistory) {
                return;
            }
            history.splice(historyIndex + 1);
            history.push(capture());
            historyIndex = history.length - 1;
            refresh();
        });
    });

    undoButton.addEventListener('click', () => {
        if (historyIndex > 0) {
            historyIndex -= 1;
            applyState(history[historyIndex]);
        }
    });

    redoButton.addEventListener('click', () => {
        if (historyIndex < history.length - 1) {
            historyIndex += 1;
            applyState(history[historyIndex]);
        }
    });

    if (timer) {
        const initialElapsed = Math.max(0, Number(timer.dataset.decorridoMs) || 0);
        const browserStart = Date.now();
        const showTime = () => {
            const totalSeconds = Math.floor((initialElapsed + Date.now() - browserStart) / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            timer.textContent = hours > 0
                ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
                : `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        };
        showTime();
        window.setInterval(showTime, 1000);
    }

    refresh();
})();
