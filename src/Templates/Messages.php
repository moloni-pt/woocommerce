<?php if (!empty($message)) : ?>
    <?php if (is_array($message)) : ?>
        <?php foreach ($message as $item) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?= $message ?></p>
                <button type="button" class="notice-dismiss"><span
                            class="screen-reader-text"><?= __("Remover o aviso"); ?></span>
                </button>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div id="message" class="updated notice is-dismissible">
            <p><?= $message ?></p>
            <button type="button" class="notice-dismiss"><span
                        class="screen-reader-text"><?= __("Remover o aviso"); ?></span>
            </button>
        </div>
    <?php endif; ?>
<?php endif; ?>
