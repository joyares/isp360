<?php
require '../../includes/header.php';
?>
<div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between"><button class="btn btn-falcon-default btn-sm" type="button"><span class="fas fa-arrow-left"></span></button>
              <div class="d-flex"><button class="btn btn-sm btn-falcon-default d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#contactDetailsOffcanvas" aria-controls="contactDetailsOffcanvas"><span class="fas fa-tasks" data-fa-transform="shrink-2"></span><span class="ms-1">To-do</span></button>
                <div class="bg-300 mx-3 d-xl-none" style="width:1px; height:29px"></div><button class="btn btn-falcon-default btn-sm me-2" type="button"><span class="fas fa-edit"></span><span class="d-none d-xl-inline-block ms-1">Edit</span></button>
                <button class="btn btn-falcon-default btn-sm d-none d-sm-block" type="button"><span class="fas fa-sync-alt"></span><span class="d-none d-xl-inline-block ms-1">Convert to Agent</span></button>
                <button class="btn btn-falcon-default btn-sm btn-sm d-none d-sm-block mx-2" type="button"><span class="fas fa-lock"></span><span class="d-none d-xl-inline-block ms-1">Send Activation Email</span></button>
                <button class="btn btn-falcon-default btn-sm d-none d-sm-block me-2" type="button"><span class="fas fa-trash-alt"></span><span class="d-none d-xl-inline-block ms-1">Delete</span></button>
                <button class="btn btn-falcon-default btn-sm d-none d-sm-block me-2" type="button"><span class="fas fa-key"></span><span class="d-none d-xl-inline-block ms-1">Change Password</span></button>
                <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="preview-dropdown" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-v fs-11"></span></button>
                  <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="preview-dropdown"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a><a class="dropdown-item d-sm-none" href="#!">Convert to Agent</a><a class="dropdown-item d-sm-none" href="#!">Send Activation Email</a><a class="dropdown-item d-sm-none" href="#!">Delete</a><a class="dropdown-item d-sm-none" href="#!">Change Password</a>
                    <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-xxl-3 col-xl-4 order-xl-1">
              <div class="position-xl-sticky top-0">
                <div class="card">
                  <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <h6 class="mb-0">Contact Information</h6>
                    <div class="dropdown font-sans-serif btn-reveal-trigger"><button class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button" id="dropdown-contact-information" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                      <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-contact-information"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                        <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body bg-body-tertiary">
                    <div class="card border rounded-3 bg-white dark__bg-1000 shadow-none">
                      <div class="bg-holder bg-card d-none d-sm-block d-xl-none" style="background-image:url(../../assets/img/icons/spot-illustrations/corner-2.png);"></div><!--/.bg-holder-->
                      <div class="card-body row g-0 flex-column flex-sm-row flex-xl-column z-1 align-items-center">
                        <div class="col-auto text-center me-sm-x1 me-xl-0"><img class="ticket-preview-avatar" src="../../assets/img/team/5-thumb.png" alt="" /></div>
                        <div class="col-sm-8 col-md-6 col-lg-4 col-xl-12 ps-sm-1 ps-xl-0">
                          <p class="fw-semi-bold mb-0 text-800 text-center text-sm-start text-xl-center mb-3 mt-3 mt-sm-0 mt-xl-3">Matt Rogers</p>
                          <div class="d-flex gap-2 justify-content-center"><button class="btn btn-primary btn-sm px-2 text-nowrap w-50"><span class="fas fa-plus me-1" data-fa-transform="shrink-3 down-1"></span><span class="fs-11">New Ticket</span></button>
                            <button class="btn btn-sm btn-falcon-default w-50"><span class="fas fa-phone-alt me-1" data-fa-transform="shrink-4"></span><span class="fs-11">Call</span></button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="border rounded-3 p-x1 mt-3 bg-white dark__bg-1000 row mx-0 g-0">
                      <div class="col-md-6 col-xl-12 pe-md-4 pe-xl-0">
                        <div class="mb-4">
                          <h6 class="mb-1 false">Email</h6><a class="fs-10" href="mailto:mattrogers@gmail.com">mattrogers@gmail.com</a>
                        </div>
                        <div class="mb-4">
                          <h6 class="false mb-1">Phone Number</h6><a class="fs-10" href="tel:+6(855)747677">+6(855) 747 677</a>
                        </div>
                        <div class="mb-4">
                          <h6 class="false false">Location</h6>
                          <p class="mb-0 text-700 fs-10">936 N. Fairground Rd.Farnham, QC J2N 5E9</p>
                        </div>
                        <div class="mb-4">
                          <h6 class="false false">Language</h6>
                          <p class="mb-0 text-700 fs-10">English</p>
                        </div>
                        <div class="mb-4 mb-md-0 mb-xl-4">
                          <h6 class="false false">Account Verified by Twitter</h6>
                          <p class="mb-0 text-700 fs-10">No</p>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl-12 ps-md-4 ps-xl-0">
                        <div class="mb-4">
                          <h6 class="false false">Subscription</h6>
                          <p class="mb-0 text-700 fs-10">Active</p>
                        </div>
                        <div class="mb-4">
                          <h6 class="false false">OS</h6>
                          <p class="mb-0 text-700 fs-10">macOS Monterey</p>
                        </div>
                        <div class="mb-4">
                          <h6 class="false false">Browser</h6>
                          <p class="mb-0 text-700 fs-10">Google Chrome 98.0.2563</p>
                        </div>
                        <div class="mb-4">
                          <h6 class="false false">IP</h6>
                          <p class="mb-0 text-700 fs-10">52.119.132.297</p>
                        </div>
                        <h6>Tag</h6><a class="badge border link-secondary me-1 text-decoration-none fs-11" href="#!">New</a><a class="badge border link-secondary me-1 text-decoration-none fs-11" href="#!">Payment</a><a class="badge border link-secondary text-decoration-none fs-11" href="#!">Subscribe</a>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="offcanvas offcanvas-end offcanvas-contact-info" tabindex="-1" id="contactDetailsOffcanvas" aria-labelledby="contactDetailsOffcanvasLabelCard">
                  <div class="offcanvas-header d-xl-none d-flex flex-between-center d-xl-none bg-body-tertiary">
                    <h6 class="fs-9 mb-0 fw-semi-bold">To-do List</h6><button class="btn-close text-reset d-xl-none shadow-none" id="contactDetailsOffcanvasLabelCard" type="button" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                  </div>
                  <div class="offcanvas-body scrollbar scrollbar-none-xl p-0"><button class="btn btn-falcon-default btn-sm d-flex align-items-center mb-x1 d-xl-none ms-x1 mt-x1" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3"></span><span class="ms-1">Add</span></button>
                    <div class="border-bottom border-xl-0 border-200"></div>
                    <div class="card shadow-none shadow-show-xl mt-xl-3">
                      <div class="card-header d-flex flex-between-center bg-body-tertiary d-none d-xl-flex">
                        <h6 class="mb-0">To-do List</h6><button class="btn btn-falcon-default btn-sm d-flex align-items-center" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3"></span><span class="ms-1">Add</span></button>
                      </div>
                      <div class="card-body ticket-todo-list scrollbar-overlay h-auto">
                        <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 border-bottom mb-3">
                          <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox" id="ticket-checkbox-todo-0" /><label class="form-check-label w-100 pe-3" for="ticket-checkbox-todo-0"><span class="mb-1 text-700 d-block">Sidenav text cutoff rendering issue</span><span class="fs-11 text-600 lh-base font-base fw-normal d-block mb-2">Problem with Falcon theme</span></label></div>
                          <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span class="fas fa-trash text-danger"></span></button></div>
                        </div>
                        <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 border-bottom mb-3">
                          <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox" id="ticket-checkbox-todo-1" /><label class="form-check-label w-100 pe-3" for="ticket-checkbox-todo-1"><span class="mb-1 text-700 d-block">Notify when the WebPack release is ready</span><span class="fs-11 text-600 lh-base font-base fw-normal d-block mb-2">Falcon Bootstarp 5</span></label></div>
                          <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span class="fas fa-trash text-danger"></span></button></div>
                        </div>
                        <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 mb-0">
                          <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox" id="ticket-checkbox-todo-2" /><label class="form-check-label w-100 pe-3 mb-0" for="ticket-checkbox-todo-2"><span class="mb-1 text-700 d-block">File Attachments</span><span class="fs-11 text-600 lh-base font-base fw-normal d-block mb-0">Sending attachments automatically attaches them to the notification email that the client receives as well as making them accessible through.</span></label></div>
                          <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span class="fas fa-trash text-danger"></span></button></div>
                        </div>
                      </div>
                      <div class="card-footer border-top border-200 text-xl-center p-0"><a class="btn btn-link btn-sm fw-medium py-x1 py-xl-2 px-x1" href="#!">View all<span class="fas fa-chevron-right ms-1 fs-11"></span></a></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xxl-9 col-xl-8">
              <div class="card overflow-hidden">
                <div class="card-header p-0 scrollbar-overlay border-bottom">
                  <ul class="nav nav-tabs border-0 tab-contact-details flex-nowrap" id="contact-details-tab" role="tablist">
                    <li class="nav-item text-nowrap" role="presentation"><a class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1 active" id="contact-timeline-tab" data-bs-toggle="tab" href="#timeline" role="tab" aria-controls="timeline" aria-selected="true"><span class="fas fa-stream icon"></span>
                        <h6 class="mb-0 text-600">Timeline</h6>
                      </a></li>
                    <li class="nav-item text-nowrap" role="presentation"><a class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-tickets-tab" data-bs-toggle="tab" href="#tickets" role="tab" aria-controls="tickets" aria-selected="false"><span class="fas fa-ticket-alt"></span>
                        <h6 class="mb-0 text-600">Tickets</h6>
                      </a></li>
                    <li class="nav-item text-nowrap" role="presentation"><a class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-notes-tab" data-bs-toggle="tab" href="#notes" role="tab" aria-controls="notes" aria-selected="false"><span class="fas fa-file-alt icon"></span>
                        <h6 class="mb-0 text-600">Notes</h6>
                      </a></li>
                  </ul>
                </div>
                <div class="tab-content">
                  <div class="card-body bg-body-tertiary tab-pane active" id="timeline" role="tabpanel" aria-labelledby="contact-timeline-tab">
                    <div class="timeline-vertical py-0">
                      <div class="timeline-item timeline-item-start mb-3">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">25 September</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Got a new television #377</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">Thank you for replacing my broken television with a new one.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>10:28 AM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Urgent"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Urgent</h6>
                                  </div><small class="badge rounded badge-subtle-success false">Recent</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="timeline-item timeline-item-end mb-3">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">23 September</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Subscription Issue #362</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">On November 2, 2022, your membership at Falcon is going to expire. We really hope that you have benefited from your membership.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>09:26 PM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Low"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Low</h6>
                                  </div><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="timeline-item timeline-item-start mb-3">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">20 September</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Received a broken TV #345</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">My television from your website was delivered with a cracked screen. I need assistance getting a refund or a replacement.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>01:06 PM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Urgent"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Urgent</h6>
                                  </div><small class="badge rounded badge-subtle-success false">Recent</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="timeline-item timeline-item-end mb-3">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">03 September</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Payment failed #324</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">Your payment failed while I tried to make a payment on your website, I was told. My card was, however, billed.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>11:06 PM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Medium"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Medium</h6>
                                  </div><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="timeline-item timeline-item-start mb-3">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">24 August</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Password change #234</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">I must modify my password. If I make a modification, will I lose access to my account? I have a lot of items in my cart and don't want to go looking for them again.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>10:08 AM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Urgent"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Urgent</h6>
                                  </div><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="timeline-item timeline-item-end mb-0">
                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300"><span class="fs-8 fas fa-envelope"></span></div>
                        <div class="row">
                          <div class="col-lg-6 timeline-item-time">
                            <div>
                              <h6 class="mb-0 text-700">2022</h6>
                              <p class="fs-11 text-500 font-sans-serif">20 August</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="timeline-item-content arrow-bg-white">
                              <div class="timeline-item-card bg-white dark__bg-1100"><a href="tickets-preview.php">
                                  <h5 class="mb-2 hover-primary">Email Address change #202</h5>
                                </a>
                                <p class="fs-10 border-bottom mb-3 pb-4 text-600">My email address needs to be updated. I'm curious if changing it will result in me losing access to my account. I've put a lot of items in my shopping basket and don't want to search for them again.</p>
                                <div class="d-flex flex-wrap pt-2">
                                  <h6 class="mb-0 text-600 lh-base"><span class="far fa-clock me-1"></span>12:26 PM</h6>
                                  <div class="d-flex align-items-center ms-auto me-2 me-sm-x1 me-xl-2 me-xxl-x1">
                                    <div class="dot me-0 me-sm-2 me-xl-0 me-xxl-2 bg-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Low"></div>
                                    <h6 class="mb-0 text-700 d-none d-sm-block d-xl-none d-xxl-block">Low</h6>
                                  </div><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card-body tab-pane p-0" id="tickets" role="tabpanel" aria-labelledby="contact-tickets-tab">
                    <div class="bg-body-tertiary d-flex flex-column gap-3 p-x1">
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Got a new television | Order #377</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">25 September, 2022</h6><small class="badge rounded badge-subtle-success false">Recent</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:100"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#e63757" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Urgent</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option selected="selected">Anindya</option>
                            <option>Nowrin</option>
                            <option>Khalid</option>
                            <option>Shajeeb</option>
                          </select>
                        </div>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Subscription Issue | Order #362</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">23 September, 2022</h6><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:25"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#00D27B" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Low</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option>Anindya</option>
                            <option>Nowrin</option>
                            <option selected="selected">Khalid</option>
                            <option>Shajeeb</option>
                          </select>
                        </div>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Received a broken TV | Order #345</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">20 September, 2022</h6><small class="badge rounded badge-subtle-success false">Recent</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:100"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#e63757" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Urgent</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option>Anindya</option>
                            <option selected="selected">Nowrin</option>
                            <option>Khalid</option>
                            <option>Shajeeb</option>
                          </select>
                        </div>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Payment failed | Order #324</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">03 September, 2022</h6><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:50"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#2A7BE4" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Medium</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option selected="selected">Anindya</option>
                            <option>Nowrin</option>
                            <option>Khalid</option>
                            <option>Shajeeb</option>
                          </select>
                        </div>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Password change | Order #234</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">24 August, 2022</h6><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:100"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#e63757" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Urgent</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option>Anindya</option>
                            <option selected="selected">Nowrin</option>
                            <option>Khalid</option>
                            <option>Shajeeb</option>
                          </select>
                        </div>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center">
                        <div>
                          <p class="fw-semi-bold"><a href="tickets-preview.php">Email Address change | Order #202</a></p>
                          <div class="d-flex align-items-center">
                            <h6 class="mb-0 me-3 text-800">20 August, 2022</h6><small class="badge rounded badge-subtle-secondary dark__bg-1000">Closed</small>
                          </div>
                        </div>
                        <div class="border-bottom mt-4 mb-x1"></div>
                        <div class="d-flex justify-content-between ms-auto">
                          <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0 ms-xxl-4" style="width:7.5rem;">
                            <div style="--falcon-circle-progress-bar:25"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                                <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                                <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="#00D27B" stroke-width="12"></circle>
                              </svg></div>
                            <h6 class="mb-0 text-700">Low</h6>
                          </div><select class="form-select form-select-sm" aria-label="agents actions" style="width:9.375rem;">
                            <option>Select Agent</option>
                            <option>Anindya</option>
                            <option>Nowrin</option>
                            <option>Khalid</option>
                            <option selected="selected">Shajeeb</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card-body tab-pane p-0" id="notes" role="tabpanel" aria-labelledby="contact-notes-tab">
                    <div class="bg-body-tertiary d-flex flex-column gap-3 p-x1">
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Not able to access the system</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option selected="selected">Anindya</option>
                              <option>Nowrin</option>
                              <option>Khalid</option>
                              <option>Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-0" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-0"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">28 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">12:06 AM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">The PS4's hard drive is most likely the source of this CE-34335-8 safe mode error notice. Try these techniques to fix the hard drive issue if your PS4 won't start and won't let you access system storage because of error number CE-34335-8.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">No refund was requested</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option>Anindya</option>
                              <option>Nowrin</option>
                              <option selected="selected">Khalid</option>
                              <option>Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-1" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-1"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">25 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">03:18 PM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">It only takes a little while for a consumer to arrive on your door asking for a refund if you sell things online or in a physical store. And instead of closing that door all the way, think of a different approach.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Use case for online ticket notes</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option>Anindya</option>
                              <option selected="selected">Nowrin</option>
                              <option>Khalid</option>
                              <option>Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-2" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-2"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">22 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">10:21 AM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Using the inline ticket notes allows you to take notes while interacting with consumers. You may jot down notes while assisting a customer over live chat or over the phone, for instance. Aside from that.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Github Uploaded of the Conscious Administrator</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option>Anindya</option>
                              <option>Nowrin</option>
                              <option>Khalid</option>
                              <option selected="selected">Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-3" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-3"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">15 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">12:21 PM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Are they really that dissimilar, even though those are mock-ups and this is politics? She may simply have my card, I believe.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Selection of a design team</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option selected="selected">Anindya</option>
                              <option>Nowrin</option>
                              <option>Khalid</option>
                              <option>Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-4" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-4"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">11 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">10:11 PM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">One designer can make up a design team, as can a group of designers who take on various tasks and employ various techniques and tools to reach a single objective. The shared objective can be achieved by creating a website, a mobile application, or any other design project.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Quickness of Reaction</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option>Anindya</option>
                              <option>Nowrin</option>
                              <option>Khalid</option>
                              <option selected="selected">Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-5" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-5"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">09 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">12:22 AM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">It has been designed particularly for WordPress, as opposed to other Frameworks which attempt to cover everything.</p>
                      </div>
                      <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                        <div class="row flex-between-center">
                          <div class="col-12 col-md-7 col-xl-12 col-xxl-8 order-1 order-md-0 order-xl-1 order-xxl-0">
                            <h5 class="mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-0 mt-xl-x1 mt-xxl-0 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">Cultivate a design-oriented culture</h5>
                          </div>
                          <div class="col-12 col-md-auto col-xl-12 col-xxl-auto d-flex flex-between-center"><select class="form-select form-select-sm me-2 w-auto" aria-label="agents actions">
                              <option>Select Agent</option>
                              <option>Anindya</option>
                              <option>Nowrin</option>
                              <option selected="selected">Khalid</option>
                              <option>Shajeeb</option>
                            </select>
                            <div class="dropdown font-sans-serif"><button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" id="notes-dropdown-6" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                              <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="notes-dropdown-6"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        <h6 class="mb-0 mt-2"><span class="fas fa-clock text-primary me-2"></span><span class="text-600">05 Sep, 2020</span><span class="text-500"> at </span><span class="text-600">10:21 AM</span></h6>
                        <p class="w-lg-75 w-xl-100 w-xxl-75 mb-0 border-top border-top-md-0 border-top-xl border-top-xxl-0 mt-x1 mt-md-4 mt-xl-x1 mt-xxl-4 pt-x1 pt-md-0 pt-xl-x1 pt-xxl-0 border-200 border-xl-200">By teaching your designers to put the needs of the customer first and coordinating design objectives with corporate objectives, you can foster a culture of design strategy. Everything your design team does should be based on a design strategy.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
<?php
require '../../includes/footer.php';
?>
