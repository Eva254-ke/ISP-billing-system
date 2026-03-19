<!-- Add Router Modal -->
<div class="modal fade" id="addRouterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Router</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRouterForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Router Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Main Hotspot" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Router Type *</label>
                            <select class="form-select" name="type" required>
                                <option value="hotspot">MikroTik Hotspot</option>
                                <option value="pppoe">MikroTik PPPoE</option>
                                <option value="both">Hotspot + PPPoE</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IP Address *</label>
                            <input type="text" class="form-control" name="ip" placeholder="192.168.88.1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">API Port</label>
                            <input type="number" class="form-control" name="port" value="8728">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" value="admin" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g., Nairobi Office">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRouter">
                    <i class="fas fa-save me-1"></i>Save Router
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('saveRouter').addEventListener('click', function() {
        // Mock save - in production, this would submit to backend
        Swal.fire({
            title: 'Saving...',
            text: 'Testing connection to router',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        setTimeout(() => {
            Swal.fire('Success!', 'Router added successfully', 'success')
                .then(() => {
                    location.reload();
                });
        }, 2000);
    });
</script>
@endpush
