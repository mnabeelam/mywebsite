<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

site_page(
    'contact',
    'Contact',
    'Request a quote for IT consulting, advisory sessions, or general inquiries.',
    'contact.php',
    function () {
        site_page_intro(
            'Contact',
            'Request a Quote',
            'Tell me what you need. I will reply by email with next steps and pricing.'
        );
        ?>
<section class="section-block contact-section reveal">
  <div class="contact-split reveal-stagger">
    <div class="contact-trust reveal-child">
      <h3>What happens next</h3>
      <p>Your message goes directly to me — not a ticketing system. I typically reply within one business day.</p>
      <ul>
        <li>Free discovery call for new clients</li>
        <li>Written proposal for project work</li>
        <li>Invoice or bank transfer — no card charges on this site</li>
      </ul>
    </div>
    <form id="contactForm" class="contact-form section-panel reveal-child">
      <div>
        <label for="contactService">Service interested in</label>
        <select id="contactService" name="service" required>
          <option value="Discovery Call">Discovery Call (Free · 30 min)</option>
          <option value="Advisory Session">Advisory Session (Custom quote)</option>
          <option value="Project Consulting">Project Consulting (Proposal)</option>
          <option value="General Inquiry">General inquiry</option>
        </select>
      </div>
      <div>
        <label for="contactName">Your name</label>
        <input id="contactName" name="name" type="text" autocomplete="name" required maxlength="120">
      </div>
      <div>
        <label for="contactEmail">Your email</label>
        <input id="contactEmail" name="email" type="email" autocomplete="email" required maxlength="180">
      </div>
      <div>
        <label for="contactMessage">Message</label>
        <textarea id="contactMessage" name="message" required maxlength="3000"></textarea>
      </div>
      <button id="contactSubmit" type="submit">Send Message</button>
      <p id="contactFeedback" class="contact-feedback" role="status" aria-live="polite"></p>
    </form>
  </div>
</section>

<section class="section-block reveal">
  <div class="section-header">
    <p class="section-eyebrow">Connect</p>
    <h2>Connect With Me</h2>
    <p class="section-desc">Reach out for collaboration, consulting, or professional networking.</p>
  </div>
  <div class="social-card">
    <div class="social-links">
      <a class="social-link" href="https://www.linkedin.com/in/nabeel-a-1370b4358">LinkedIn Profile</a>
      <a class="social-link" href="https://github.com/mnabeelam">GitHub Projects</a>
      <a class="social-link" href="mailto:mnabeelam@gmail.com">mnabeelam@gmail.com</a>
      <a class="social-link" href="https://it.gift.edu.pk">it.gift.edu.pk</a>
    </div>
  </div>
</section>
        <?php
    }
);
