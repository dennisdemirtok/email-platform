<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list-alt me-2"></i>Email Event Logs</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($emailEvents) && is_array($emailEvents)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Subject</th>
                            <th>Recipient</th>
                            <th>Event Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emailEvents as $emailEvent): ?>
                            <tr>
                                <td>
                                    <?php
                                        $formattedDate = 'N/A';
                                        if (isset($emailEvent['event_created_at'])) {
                                            $created_at = new DateTime($emailEvent['event_created_at']);
                                            $formattedDate = $created_at->format('d M Y, H:i:s');
                                        }
                                    ?>
                                    <small class="text-muted"><?= esc($formattedDate) ?></small>
                                </td>
                                <td><?= esc($emailEvent['subject'] ?? '') ?></td>
                                <td><?= esc($emailEvent['recipient'] ?? '') ?></td>
                                <td>
                                    <?php
                                        $eventType = $emailEvent['event_type'] ?? 'unknown';
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
            <div class="empty-state">
                <i class="fas fa-list-alt d-block"></i>
                <h3>No Email Events Found</h3>
                <p>Email event logs will appear here once campaigns are sent and events are tracked.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
