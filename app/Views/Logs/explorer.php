<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-search me-2"></i>Email Events Explorer</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($emails) && is_iterable($emails)): ?>
            <?php $index = 1; ?>
            <?php foreach ($emails as $email): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>
                            <span class="badge bg-secondary me-2"><?= esc($index++) ?></span>
                            <?= esc($email['_id'] ?? '') ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($email['events']) && is_iterable($email['events'])): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Event Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($email['events'] as $event): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                        $eventType = $event['event_type'] ?? 'unknown';
                                                        $badgeClass = 'badge-pending';
                                                        if (strpos($eventType, 'delivered') !== false) {
                                                            $badgeClass = 'badge-delivered';
                                                        } elseif (strpos($eventType, 'opened') !== false) {
                                                            $badgeClass = 'badge-opened';
                                                        } elseif (strpos($eventType, 'clicked') !== false) {
                                                            $badgeClass = 'badge-clicked';
                                                        } elseif (strpos($eventType, 'bounced') !== false) {
                                                            $badgeClass = 'badge-bounced';
                                                        } elseif (strpos($eventType, 'complained') !== false) {
                                                            $badgeClass = 'badge-failed';
                                                        } elseif (strpos($eventType, 'sent') !== false) {
                                                            $badgeClass = 'badge-sent';
                                                        }
                                                    ?>
                                                    <span class="badge-status <?= $badgeClass ?>"><?= esc($eventType) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state py-3">
                                <p class="mb-0">No events recorded for this email.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search d-block"></i>
                <h3>No Emails Found</h3>
                <p>Email events grouped by unique email will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
