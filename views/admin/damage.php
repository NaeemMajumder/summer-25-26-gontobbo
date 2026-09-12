<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="card">

    <div class="form-row-inline">
        <h3 class="card-title" style="margin:0;">Damage Reports</h3>
        <form method="get" action="index.php" class="search-form-compact">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="action" value="damage">
            <input type="text" name="q" value="<?= esc($_GET['q'] ?? '') ?>"
                placeholder="Search description, driver, bus..." style="width:260px;">
        </form>
    </div>

    <?php if (empty($reports)): ?>

        <div class="empty-box">
            <p>No damage reports.</p>
        </div>

    <?php else: ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Bus</th>
                    <th>Driver</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><?= esc($r['description']) ?></td>
                        <td><?= $r['bus_number'] ? esc($r['bus_number']) : '<span class="muted-text">—</span>' ?></td>
                        <td><?= esc($r['driver_name']) ?></td>
                        <td><?= $r['created_at'] ? esc(substr($r['created_at'], 0, 10)) : '<span class="muted-text">—</span>' ?>
                        </td>
                        <td>
                            <?php
                            $map = ['open' => 'danger', 'reviewing' => 'warning', 'resolved' => 'success'];
                            $cls = $map[$r['status']] ?? 'warning';
                            ?>
                            <span class="pill pill-<?= $cls ?>"><?= esc(ucfirst($r['status'])) ?></span>
                        </td>
                        <td>
                            <?php if ($r['status'] !== 'resolved'): ?>
                                <form method="post" action="index.php?page=admin&action=resolvedamage"
                                    onsubmit="return confirm('Mark this report as resolved?');" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="incident_id" value="<?= (int) $r['incident_id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-small">Resolve</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-text">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>