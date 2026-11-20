{{-- CameraCircle + InstructionText + ProgressSteps (docs/DESIGN.md §6). Driven by resources/js/face/main.js. --}}
<section data-section="scanner" hidden>
    <div class="camera-circle" data-circle>
        <video data-video playsinline muted></video>
    </div>
    <p class="steps" data-steps></p>
    <p class="instruction" data-instruction aria-live="polite"></p>
</section>
