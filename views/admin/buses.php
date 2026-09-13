<?php require __DIR__ . '/../partials/header.php'; ?>

<!-- ADD / EDIT FORM -->
<section class="card card-narrow">

    <h3 class="card-title"><?= $editBus ? 'Edit Bus' : 'Add New Bus' ?></h3>

    <form method="post"
          action="index.php?page=admin&action=<?= $editBus ? 'updatebus' : 'addbus' ?>"
          novalidate>
        <?php csrf_field(); ?>

        <?php if ($editBus): ?>
            <input type="hidden" name="bus_id" value="<?= (int) $editBus['bus_id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="bus_number">Bus Number</label>
            <input id="bus_number" name="bus_number" type="text"
                   value="<?= esc($editBus['bus_number'] ?? '') ?>" placeholder="e.g. DHAKA-BA-1234">
        </div>

        <div class="form-group">
            <label for="name">Name / Label</label>
            <input id="name" name="name" type="text"
                   value="<?= esc($editBus['name'] ?? '') ?>" placeholder="e.g. Green Line">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <?php $t = $editBus['type'] ?? ''; ?>
                    <option value="AC"     <?= $t === 'AC' ? 'selected' : '' ?>>AC</option>
                    <option value="Non-AC" <?= $t === 'Non-AC' ? 'selected' : '' ?>>Non-AC</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php $s = $editBus['status'] ?? 'active'; ?>
                    <option value="active"      <?= $s === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="maintenance" <?= $s === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="total_seats">Total Seats</label>
                <input id="total_seats" name="total_seats" type="number" min="1"
                       value="<?= esc($editBus['total_seats'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="service_trip_limit">Service Trip Limit</label>
                <input id="service_trip_limit" name="service_trip_limit" type="number" min="1"
                       value="<?= esc($editBus['service_trip_limit'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= $editBus ? 'Update Bus' : 'Add Bus' ?></button>

        <?php if ($editBus): ?>
            <a href="index.php?page=admin&action=buses" class="btn btn-ghost">Cancel</a>
        <?php endif; ?>

    </form>
</section>


<!-- SEARCH + TABLE -->
<section class="card">

    <div class="form-row-inline">
        <h3 class="card-title" style="margin:0;">All Buses</h3>
        <form method="get" action="index.php" class="search-form-compact">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="action" value="buses">
            <input type="text" name="q" value="<?= esc($_GET['q'] ?? '') ?>"
                   placeholder="Search number, name, type..." style="width:260px;">
        </form>
    </div>

    <?php if (empty($buses)): ?>

        <div class="empty-box"><p>No buses found.</p></div>

    <?php else: ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Seats</th>
                    <th>Status</th>
                    <th>Service Limit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buses as $b): ?>
                    <tr>
                        <td><?= esc($b['bus_number']) ?></td>
                        <td><?= esc($b['name']) ?></td>
                        <td>
                            <span class="badge badge-<?= $b['type'] === 'AC' ? 'ac' : 'nonac' ?>">
                                <?= esc($b['type']) ?>
                            </span>
                        </td>
                        <td><?= (int) $b['total_seats'] ?></td>
                        <td>
                            <span class="pill pill-<?= $b['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= esc(ucfirst($b['status'])) ?>
                            </span>
                        </td>
                        <td><?= (int) $b['trips_since_service'] ?> / <?= (int) $b['service_trip_limit'] ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="index.php?page=admin&action=buses&edit=<?= (int) $b['bus_id'] ?>"
                                   class="btn btn-ghost btn-small">Edit</a>

                                <form method="post" action="index.php?page=admin&action=deletebus"
                                      onsubmit="return confirm('Delete this bus?');" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="bus_id" value="<?= (int) $b['bus_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>